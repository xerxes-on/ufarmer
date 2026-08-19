<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Modules\JobsServices\Services\AiImport\Worker\WorkerRowValidator;
use Tests\TestCase;

/**
 * The one definition of a publishable worker row (UFARM-2671).
 *
 * These assertions exist because the rules used to be written twice — once in
 * the mapper, once on the review screen — and the copies disagreed.
 */
class WorkerRowValidatorTest extends TestCase
{
    public function test_a_complete_row_has_nothing_blocking_it(): void
    {
        $errors = (new WorkerRowValidator)->validate($this->row());

        $this->assertSame([], $errors['blocking']);
        $this->assertSame([], $errors['warnings']);
    }

    public function test_the_three_things_a_row_cannot_publish_without(): void
    {
        $errors = (new WorkerRowValidator)->validate([
            ...$this->row(),
            'phone' => null,
            'phone_raw' => null,
            'category_id' => null,
            'title' => null,
        ]);

        $this->assertSame(
            ['phone_missing', 'category_unmatched', 'title_missing'],
            $errors['blocking'],
        );
    }

    public function test_each_kind_of_bad_phone_keeps_its_own_name(): void
    {
        $validator = new WorkerRowValidator;

        // "Too many digits" is the one that matters: it means a typo nobody
        // may guess at, and it used to be reported as a plain invalid number.
        $this->assertSame(
            ['phone_ambiguous'],
            $validator->validate([...$this->row(), 'phone' => '9989099170007'])['blocking'],
        );

        $this->assertSame(
            ['phone_invalid'],
            $validator->validate([...$this->row(), 'phone' => '12345'])['blocking'],
        );

        $this->assertSame(
            ['phone_missing'],
            $validator->validate([...$this->row(), 'phone' => null, 'phone_raw' => null])['blocking'],
        );
    }

    public function test_a_raw_phone_is_re_read_when_the_normalised_one_is_missing(): void
    {
        // The mapper stores the sheet's original spelling precisely so a row
        // rejected on import can be re-judged after an edit.
        $errors = (new WorkerRowValidator)->validate([
            ...$this->row(),
            'phone' => null,
            'phone_raw' => '9989099170007',
        ]);

        $this->assertSame(['phone_ambiguous'], $errors['blocking']);
    }

    public function test_the_warnings_an_edit_used_to_discard_survive(): void
    {
        $errors = (new WorkerRowValidator)->validate([
            ...$this->row(),
            'region_id' => null,
            'city_id' => null,
            'experience_years' => null,
            'coords_source' => 'region',
            'price_negotiable' => true,
        ]);

        $this->assertSame([], $errors['blocking']);
        $this->assertEqualsCanonicalizing(
            ['region_unmatched', 'city_unmatched', 'coords_fallback', 'price_negotiable', 'experience_missing'],
            $errors['warnings'],
        );
    }

    public function test_an_unmatched_place_the_sheet_never_named_is_not_a_warning(): void
    {
        // Nothing was lost in translation if the sheet said nothing at all.
        $errors = (new WorkerRowValidator)->validate([
            ...$this->row(),
            'region_id' => null,
            'region_name' => null,
            'city_id' => null,
            'city_name' => null,
        ]);

        $this->assertSame([], $errors['warnings']);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        return [
            'phone' => '998901234567',
            'phone_raw' => '998 90 123-45-67',
            'category_id' => 1,
            'title' => 'Томчилатиб суғориш',
            'region_id' => 1,
            'region_name' => 'Тошкент ш',
            'city_id' => 1,
            'city_name' => 'Чилонзор тумани',
            'experience_years' => 5.0,
            'coords_source' => 'sheet',
            'price_negotiable' => false,
        ];
    }
}
