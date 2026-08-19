<?php

namespace Tests\Unit\Rules;

use Modules\General\Rules\NoContentCorruption;
use Tests\TestCase;

class NoContentCorruptionTest extends TestCase
{
    public function test_it_rejects_known_corrupted_tokens(): void
    {
        $this->assertTrue($this->fails('dalada yoki qauta ishlash jarayonida'));
    }

    public function test_it_does_not_flag_the_correct_word(): void
    {
        $this->assertFalse($this->fails('dalada yoki qayta ishlash jarayonida'));
    }

    public function test_it_rejects_unspaced_id_list_in_citation(): void
    {
        $this->assertTrue($this->fails('Manba: AGRO OLAM 52085,52028,52006.'));
    }

    public function test_it_allows_spaced_id_list_in_citation(): void
    {
        $this->assertFalse($this->fails('Manba: AGRO OLAM 52085, 52028, 52006.'));
    }

    public function test_it_does_not_flag_a_decimal_comma(): void
    {
        $this->assertFalse($this->fails('Tuproqqa 3,5 kg/gektar me\'yorida solinadi.'));
    }

    private function fails(string $value): bool
    {
        $failed = false;

        (new NoContentCorruption)->validate('body_uz', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }
}
