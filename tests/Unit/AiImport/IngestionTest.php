<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Modules\JobsServices\Services\AiImport\BlockSplitter;
use Modules\JobsServices\Services\AiImport\GoogleSheetUrl;
use Modules\JobsServices\Services\AiImport\SpreadsheetFlattener;
use Tests\TestCase;

/**
 * Ingestion-side parsing for AI imports (UFARM-2644).
 */
class IngestionTest extends TestCase
{
    public function test_google_sheet_url_keeps_the_tab_the_admin_was_looking_at(): void
    {
        $id = '1wivHlRc0t4ihmt9a8CTWLdB7hT3X9m6tZdeLO5AkZdE';

        $this->assertSame(
            "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv&gid=702466486",
            GoogleSheetUrl::toCsvExport("https://docs.google.com/spreadsheets/d/{$id}/edit?gid=702466486#gid=702466486"),
        );

        // Fragment-only form.
        $this->assertSame(
            "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv&gid=123",
            GoogleSheetUrl::toCsvExport("https://docs.google.com/spreadsheets/d/{$id}/edit#gid=123"),
        );

        // No tab named: ask Google for the first visible sheet rather than
        // assuming gid=0 still exists.
        $this->assertSame(
            "https://docs.google.com/spreadsheets/d/{$id}/gviz/tq?tqx=out:csv",
            GoogleSheetUrl::toCsvExport("https://docs.google.com/spreadsheets/d/{$id}/edit?usp=sharing"),
        );
    }

    public function test_non_sheet_urls_are_rejected(): void
    {
        $this->assertNull(GoogleSheetUrl::toCsvExport('https://example.com/data.csv'));
        $this->assertNull(GoogleSheetUrl::toCsvExport('https://example.com/docs.google.com/spreadsheets/d/1abc/edit'));
        $this->assertNull(GoogleSheetUrl::toCsvExport('https://docs.google.com/document/d/1abc/edit'));
        $this->assertNull(GoogleSheetUrl::toCsvExport(null));
        $this->assertFalse(GoogleSheetUrl::looksLikeSheet('https://example.com'));
    }

    public function test_a_published_sheet_uses_its_public_csv_endpoint(): void
    {
        $id = '2PACX-1vExamplePublishedSheetId';

        $this->assertSame(
            "https://docs.google.com/spreadsheets/d/e/{$id}/pub?output=csv",
            GoogleSheetUrl::toCsvExport("https://docs.google.com/spreadsheets/d/e/{$id}/pubhtml"),
        );

        $this->assertSame(
            "https://docs.google.com/spreadsheets/d/e/{$id}/pub?gid=123&single=true&output=csv",
            GoogleSheetUrl::toCsvExport("https://docs.google.com/spreadsheets/d/e/{$id}/pubhtml?gid=123&single=true"),
        );
    }

    public function test_a_small_sheet_is_one_request(): void
    {
        // Under both budgets — few enough characters to send, few enough rows
        // to ask for back — so there is nothing to gain by splitting.
        $chunks = (new BlockSplitter)->split($this->sheet(rowsPerBlock: 2), 60000);

        $this->assertCount(1, $chunks);
    }

    public function test_a_dense_sheet_is_split_even_when_it_fits_the_character_budget(): void
    {
        // The defect this guards (UFARM-2671): 17 rows of terse TSV are a few
        // thousand characters, so the old character-only check sent them as
        // one request — and the model ran out of output space partway through
        // the reply, silently returning fewer workers than the sheet held.
        $sheet = $this->sheet();
        $splitter = new BlockSplitter;

        $this->assertLessThan(60000, mb_strlen($sheet));

        $chunks = $splitter->split($sheet, 60000);

        $this->assertGreaterThan(1, count($chunks));

        // And no row is invented or lost in the process.
        $this->assertSame(
            $splitter->countDataRows($sheet),
            array_sum(array_map(fn (string $c): int => $splitter->countDataRows($c), $chunks)),
        );
    }

    public function test_counting_rows_ignores_titles_headers_and_stray_dates(): void
    {
        // 6 + 6 + 5 data rows, plus the "6" row after the stray date line.
        $this->assertSame(18, (new BlockSplitter)->countDataRows($this->sheet()));
    }

    public function test_counting_rows_folds_a_cell_that_spilled_onto_a_second_line(): void
    {
        // A cell containing a newline survives flattening as a newline, so one
        // logical row arrives as two physical lines. Only the first carries the
        // sheet's serial, which is what keeps the count honest.
        $tsv = implode("\n", [
            'Услуги Запчастей',
            "№\tФирма\tИмя\tТелефон",
            "\tФирма\tИмя\tТелефон",
            "1\tALPLER TECHNICH QK MChJ\tУткирбек Юлдашев\t998979900923\tЯнгиҳаёт тумани",
            ",Тошкент ҳалқа автомобиль йўли, 191V\tТошкент шаҳри\t\tУслуги Запчастей",
            "2\tLANDTECH QK MChJ\tМариф Мухаммедхонов\t998977389565",
        ]);

        $this->assertSame(2, (new BlockSplitter)->countDataRows($tsv));
    }

    public function test_splitting_cuts_on_block_boundaries_and_loses_no_rows(): void
    {
        $sheet = $this->sheet();
        // The splitter clamps its budget to a 1000-char floor, so a fixture
        // has to exceed that before any splitting happens at all.
        $chunks = (new BlockSplitter)->split($sheet, 1000);

        $this->assertGreaterThan(1, count($chunks));

        // Every block title survives, so each chunk stays interpretable.
        foreach (['Услуги Орошения', 'Услуги Дрона', 'Услуги Запчастей'] as $title) {
            $this->assertStringContainsString($title, implode("\n", $chunks));
        }

        // No data row is dropped on the way through.
        $original = array_filter(explode("\n", $sheet), static fn (string $l): bool => trim($l) !== '');
        $produced = array_filter(explode("\n", implode("\n", $chunks)), static fn (string $l): bool => trim($l) !== '');

        $this->assertSame([], array_diff($original, $produced));
    }

    public function test_a_lone_date_row_does_not_open_a_block(): void
    {
        // The real sheet carries a stray "29.07.2026" cell mid-block. Treating
        // it as a title would orphan every row after it from its headers.
        $chunks = (new BlockSplitter)->split($this->sheet(), 1000);
        $opensWithDate = array_filter(
            $chunks,
            static fn (string $c): bool => str_starts_with(trim($c), '29.07.2026'),
        );

        $this->assertSame([], $opensWithDate);
    }

    public function test_a_cell_containing_a_newline_stays_on_one_line(): void
    {
        // The defect this guards (UFARM-2671): a multi-line cell used to split
        // its row across several TSV lines, and the model read the fragment as
        // a section break — silently skipping the row after it. Three real
        // workers were lost that way.
        $path = tempnam(sys_get_temp_dir(), 'ufarm_flat_test_');
        file_put_contents($path, implode("\n", [
            'Услуги Для Теплиц',
            '№,Фирма,Имя,Телефон',
            ',Фирма,Имя,Телефон',
            '5,".',
            '',
            '""ACE COOLING GROUP"" ООО",Илхом Махамаджонов,998909850338',
            '6,Rufepa Technoagro,Закиров Еркин,998993713070',
        ]));

        try {
            $tsv = (new SpreadsheetFlattener)->flatten($path, 'csv');
        } finally {
            @unlink($path);
        }

        foreach (explode("\n", $tsv) as $line) {
            $this->assertStringNotContainsString("\r", $line);
        }

        // Both workers survive, each as a row of its own.
        $this->assertStringContainsString('ACE COOLING GROUP', $tsv);
        $this->assertStringContainsString('Закиров Еркин', $tsv);
        $this->assertStringContainsString(SpreadsheetFlattener::LINE_BREAK, $tsv);
        $this->assertSame(2, (new BlockSplitter)->countDataRows($tsv));
    }

    /**
     * A miniature of the real sheet: stacked blocks, two header rows each,
     * blank separators, and the stray date row. Padded past the splitter's
     * 1000-char floor so chunking is actually exercised.
     */
    private function sheet(int $rowsPerBlock = 6): string
    {
        $lines = ['Услуги Орошения(Ирригация)'];
        $lines[] = "№\tУчётная запись работника\t\t\tПрофиль работника\t\tУслуга";
        $lines[] = "\tФирма\tИмя\tТелефон\tО Себе\tКатегория\tНазвание";

        foreach (range(1, $rowsPerBlock) as $i) {
            $lines[] = "{$i}\tAGRO TOMCHI / DEBYUT (Poliv.uz)\tБахтиер Шакиров {$i}\t998 99 444-42-0{$i}\t-\tОрошение\tТомчилатиб/ёмғирлатиб суғориш";
        }

        $lines[] = '';
        $lines[] = 'Услуги Дрона';
        $lines[] = "№\tУчётная запись работника\t\t\tПрофиль работника\t\tУслуга";
        $lines[] = "\tФирма\tИмя\tТелефон\tО Себе\tКатегория\tНазвание";

        foreach (range(1, $rowsPerBlock) as $i) {
            $lines[] = "{$i}\tDronexizmat.uz\tСапаров Азамат {$i}\t95 514 08 0{$i}\t-\tУслуги Дрона\tДори сепиш хизмати";
        }

        $lines[] = '';
        $lines[] = 'Услуги Запчастей';
        $lines[] = "№\tУчётная запись работника\t\t\tПрофиль работника\t\tУслуга";
        $lines[] = "\tФирма\tИмя\tТелефон\tО Себе\tКатегория\tНазвание";

        foreach (range(1, max(1, $rowsPerBlock - 1)) as $i) {
            $lines[] = "{$i}\tPAHTAMASH MChJ\tИброхим Нарзуллаев {$i}\t99899999525{$i}\t-\tЗапчасти\tСервис курсатиш";
        }

        $lines[] = '29.07.2026';
        $lines[] = "6\tMaster.uz\tСадилло\t998973449566\t-\t\tТехник хизмат курсатиш";

        return implode("\n", $lines);
    }
}
