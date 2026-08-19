<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Flattens a spreadsheet into tab-separated text for the model (UFARM-2644).
 *
 * TSV rather than JSON or Markdown: the model reads a grid just as well from
 * tabs, and every wrapper character is a token the import pays for on every
 * chunk.
 *
 * One spreadsheet row becomes exactly one line. A newline inside a cell —
 * working hours, tiered prices, addresses — is replaced by a visible marker
 * rather than kept, because a row split across several lines stops looking
 * like a row (UFARM-2671). The wording survives; the ambiguity does not.
 *
 * OpenSpout handles both CSV and XLSX, so this needs no extra dependency —
 * both are already installed transitively via filament/tables.
 */
final class SpreadsheetFlattener
{
    /**
     * A worker sheet is tens of rows. Anything past this is a different kind
     * of file, and truncating loudly beats silently paying for 10k rows.
     */
    public const MAX_ROWS = 2000;

    /**
     * Stands in for a newline inside a cell, so a multi-line value cannot be
     * mistaken for the end of its row. Chosen to read as a break to the model
     * while being a single line to everything else.
     */
    public const LINE_BREAK = ' ⏎ ';

    /**
     * Flatten spreadsheet bytes into TSV.
     *
     * @param  string  $extension  csv|xlsx|xls — decides the reader
     *
     * @throws ExtractionFailedException
     */
    public function flatten(string $path, string $extension): string
    {
        $reader = $this->readerFor($extension);

        try {
            $reader->open($path);

            $lines = [];
            $truncated = false;

            foreach ($reader->getSheetIterator() as $sheet) {
                // A worker sheet keeps every block on one tab; a multi-tab
                // file gets each tab labelled so blocks stay attributable.
                if (count($lines) > 0) {
                    $lines[] = '';
                    $lines[] = '# Sheet: '.$sheet->getName();
                }

                foreach ($sheet->getRowIterator() as $row) {
                    if (count($lines) >= self::MAX_ROWS) {
                        $truncated = true;
                        break 2;
                    }

                    $line = $this->flattenRow($row->toArray());

                    // Blank rows are the block separators in this sheet's
                    // layout, so they are structure, not noise — but a run of
                    // them is, and trailing ones especially.
                    if ($line === '' && ($lines === [] || end($lines) === '')) {
                        continue;
                    }

                    $lines[] = $line;
                }
            }
        } catch (Throwable $e) {
            throw ExtractionFailedException::sourceUnreadable($e->getMessage());
        } finally {
            $reader->close();
        }

        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        if ($lines === []) {
            throw ExtractionFailedException::sourceUnreadable('the file contained no rows');
        }

        if ($truncated) {
            $lines[] = sprintf('… truncated at %d rows', self::MAX_ROWS);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function flattenRow(array $cells): string
    {
        $values = array_map(fn (mixed $cell): string => $this->stringify($cell), $cells);

        // Trailing empties are padding from the widest row in the sheet and
        // carry no meaning; interior ones mark which column a value sat in.
        while ($values !== [] && end($values) === '') {
            array_pop($values);
        }

        return implode("\t", $values);
    }

    private function stringify(mixed $cell): string
    {
        if ($cell === null || is_bool($cell)) {
            return $cell === true ? 'true' : '';
        }

        if ($cell instanceof \DateTimeInterface) {
            return $cell->format('Y-m-d');
        }

        if (is_float($cell) || is_int($cell)) {
            // Coordinates must not be rendered in scientific notation or
            // rounded — 41.258827 has to survive the round trip intact.
            return rtrim(rtrim(sprintf('%.10F', $cell), '0'), '.');
        }

        // Tabs would forge a column boundary, and so would a newline: a cell
        // containing one used to split its row across several lines, which
        // broke the one-row-per-line invariant the whole grid depends on. The
        // model then read the fragment as a section break and skipped the row
        // after it — three real workers were lost that way (UFARM-2671).
        //
        // The content still matters (working hours "Офис: 09:00-18:00 /
        // Дрон:04:00-17:00", tiered prices), so the break is kept as a visible
        // marker rather than dropped.
        $value = str_replace(["\r\n", "\r", "\n"], self::LINE_BREAK, (string) $cell);

        return trim(str_replace("\t", ' ', $value));
    }

    private function readerFor(string $extension): CsvReader|XlsxReader
    {
        if (strtolower($extension) === 'csv') {
            $options = new CsvOptions;
            // The source sheets are Google/Excel exports: comma-separated,
            // double-quoted, UTF-8 (BOM tolerated by the reader).
            $options->FIELD_DELIMITER = ',';
            $options->FIELD_ENCLOSURE = '"';

            return new CsvReader($options);
        }

        return new XlsxReader;
    }
}
