<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Illuminate\Support\Facades\Log;

/**
 * Splits flattened spreadsheet text into model-sized chunks (UFARM-2644).
 *
 * Naive character chunking is wrong for this data. The source sheet stacks
 * several service blocks in one grid, each opening with a title row and two
 * header rows; cutting mid-block would hand the model a run of data rows with
 * no headers and no block title, and it would have no way to tell which
 * service they belong to.
 *
 * So: split on block boundaries first, pack whole blocks up to the character
 * budget, and only fall back to character chunking for a single block too big
 * to fit — re-prepending that block's own header rows to every piece.
 *
 * Chunks are bounded by row count as well as by characters (UFARM-2671). The
 * budget that matters is the model's *output*: a block of 28 terse rows is a
 * few thousand input characters but 28 records to emit, and asking for all of
 * them in one answer is how an extraction runs out of output space partway
 * through. Characters bound the request; rows bound the reply.
 */
final class BlockSplitter
{
    /**
     * How many lines after a block title are treated as its headers. The
     * source sheet uses two: a merged group row, then the field row.
     */
    private const HEADER_LINES = 2;

    /**
     * Data rows per chunk. Small enough that one reply is never long enough to
     * be truncated, large enough that a four-block sheet still costs a handful
     * of calls rather than one per row.
     */
    private const MAX_ROWS_PER_CHUNK = 12;

    /**
     * Backstop against a pathological input looping up requests.
     */
    private const MAX_CHUNKS = 20;

    /**
     * @return array<int, string> chunks of TSV, each independently extractable
     */
    public function split(string $tsv, int $maxChars): array
    {
        $maxChars = max(1000, $maxChars);

        // Both budgets, or a dense sheet under the character limit sails
        // through whole — which is exactly the case that used to lose rows.
        if (mb_strlen($tsv) <= $maxChars && $this->countDataRows($tsv) <= self::MAX_ROWS_PER_CHUNK) {
            return [$tsv];
        }

        $chunks = [];
        $current = '';

        foreach ($this->blocks($tsv) as $block) {
            if (mb_strlen($block) > $maxChars || $this->countDataRows($block) > self::MAX_ROWS_PER_CHUNK) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }

                foreach ($this->splitOversizedBlock($block, $maxChars) as $piece) {
                    $chunks[] = $piece;
                }

                continue;
            }

            $candidate = $current === '' ? $block : $current."\n\n".$block;

            if (mb_strlen($candidate) > $maxChars
                || $this->countDataRows($candidate) > self::MAX_ROWS_PER_CHUNK) {
                $chunks[] = $current;
                $current = $block;

                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        $chunks = array_values(array_filter($chunks, static fn (string $c): bool => trim($c) !== ''));

        if (count($chunks) > self::MAX_CHUNKS) {
            // Dropping chunks silently is how a sheet loses its tail without
            // anyone noticing; the whole point of this class is not doing that.
            Log::warning('AI import input exceeded the chunk cap and was truncated', [
                'chunks' => count($chunks),
                'cap' => self::MAX_CHUNKS,
            ]);
        }

        return array_slice($chunks, 0, self::MAX_CHUNKS);
    }

    /**
     * How many data rows a piece of TSV holds — everything that is not a block
     * title, a header, a blank separator or a stray date/serial line.
     *
     * Also the yardstick the parser compares the model's record count against,
     * so "what counts as a row" has exactly one definition.
     *
     * The serial column is what makes this exact. A cell containing a newline
     * (addresses and working hours routinely do) survives flattening as a
     * newline, so one logical row arrives as two physical lines; counting
     * lines would over-count every such row. Only the first carries the sheet's
     * own "№", so when a numbered column is present it is the reliable signal.
     * Sheets without one fall back to counting every populated line.
     */
    public function countDataRows(string $tsv): int
    {
        $candidates = [];
        $numbered = false;

        $headersLeft = 0;

        foreach (explode("\n", $tsv) as $line) {
            if (trim($line) === '') {
                continue;
            }

            if ($this->isTitleLine($line)) {
                $headersLeft = self::HEADER_LINES;

                continue;
            }

            if ($headersLeft > 0) {
                $headersLeft--;

                continue;
            }

            // The sheet uses lone serials and dates as in-block dividers.
            if ($this->isNoiseLine($line)) {
                continue;
            }

            $cells = explode("\t", $line);

            if (count(array_filter(array_map('trim', $cells), static fn (string $c): bool => $c !== '')) < 2) {
                continue;
            }

            $hasSerial = preg_match('/^\d{1,4}$/', trim($cells[0])) === 1;
            $numbered = $numbered || $hasSerial;
            $candidates[] = $hasSerial;
        }

        return $numbered
            ? count(array_filter($candidates))
            : count($candidates);
    }

    /**
     * Cut the text into blocks at each title row.
     *
     * A title row is a line holding exactly one non-empty cell whose value is
     * not numeric — "Услуги Дрона" qualifies, a data row does not, and neither
     * does the stray "29.07.2026" row (a serial-number cell, so numeric-ish
     * and single, but it sits mid-block; see isTitleLine).
     *
     * @return array<int, string>
     */
    private function blocks(string $tsv): array
    {
        $lines = explode("\n", $tsv);
        $blocks = [];
        $current = [];

        foreach ($lines as $line) {
            if ($this->isTitleLine($line) && $current !== []) {
                $blocks[] = trim(implode("\n", $current));
                $current = [];
            }

            $current[] = $line;
        }

        if ($current !== []) {
            $blocks[] = trim(implode("\n", $current));
        }

        return array_values(array_filter($blocks, static fn (string $b): bool => trim($b) !== ''));
    }

    private function isTitleLine(string $line): bool
    {
        $cells = array_values(array_filter(
            array_map('trim', explode("\t", $line)),
            static fn (string $cell): bool => $cell !== '',
        ));

        if (count($cells) !== 1) {
            return false;
        }

        // A lone number is a serial; a lone date is the stray row this sheet
        // carries. Neither opens a block.
        if ($this->isNoiseLine($line)) {
            return false;
        }

        // Titles are short labels, not prose spilled into one cell.
        return mb_strlen($cells[0]) <= 120;
    }

    /**
     * A line carrying nothing but a serial number or a date — the dividers the
     * sheet sprinkles between rows. Not a title, and not a worker either.
     */
    private function isNoiseLine(string $line): bool
    {
        $cells = array_values(array_filter(
            array_map('trim', explode("\t", $line)),
            static fn (string $cell): bool => $cell !== '',
        ));

        if (count($cells) !== 1) {
            return false;
        }

        $value = $cells[0];

        return is_numeric(str_replace(',', '.', $value))
            || preg_match('~^\d{1,2}[./-]\d{1,2}[./-]\d{2,4}$~', $value) === 1;
    }

    /**
     * Character-chunk one oversized block, repeating its header rows so every
     * piece stays interpretable on its own. Cuts on the row budget too, since
     * a block can be well within the character budget and still hold more rows
     * than one reply should have to carry.
     *
     * @return array<int, string>
     */
    private function splitOversizedBlock(string $block, int $maxChars): array
    {
        $lines = explode("\n", $block);
        $header = implode("\n", array_slice($lines, 0, self::HEADER_LINES + 1));
        $body = array_slice($lines, self::HEADER_LINES + 1);

        $pieces = [];
        $current = $header;
        $rows = 0;

        foreach ($body as $line) {
            $candidate = $current."\n".$line;

            if ((mb_strlen($candidate) > $maxChars || $rows >= self::MAX_ROWS_PER_CHUNK)
                && $current !== $header) {
                $pieces[] = $current;
                $current = $header."\n".$line;
                $rows = $this->countDataRows($current);

                continue;
            }

            $current = $candidate;
            $rows = $this->countDataRows($current);
        }

        if (trim($current) !== trim($header)) {
            $pieces[] = $current;
        }

        return $pieces;
    }
}
