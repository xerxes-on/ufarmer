<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Modules\JobsServices\Services\AiImport\FieldParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every case here is a value that actually appears in the service-worker
 * onboarding spreadsheet (UFARM-2644), not a hypothetical.
 */
class FieldParserTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function test_phone_normalisation(string $input, ?string $phone, ?string $error): void
    {
        $result = FieldParser::phone($input);

        $this->assertSame($phone, $result['phone']);
        $this->assertSame($error, $result['error']);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string|null}>
     */
    public static function phoneProvider(): array
    {
        return [
            'spaced and hyphenated' => ['998 99 444-42-00', '998994444200', null],
            'plain with country code' => ['998937810004', '998937810004', null],
            'spaced groups' => ['998 90 515 75 55', '998905157555', null],
            'no country code' => ['95 514 08 08', '998955140808', null],
            'leading zero' => ['0901234567', '998901234567', null],
            'leading plus' => ['+998 90 123 45 67', '998901234567', null],
            // A digit too many. Repairing it would mean guessing which digit
            // is spurious, and a wrong guess silently attaches a worker to a
            // stranger's number — the key everything else is folded on.
            'thirteen digits is never repaired' => ['9989099170007', null, 'phone_ambiguous'],
            'too short' => ['12345', null, 'phone_invalid'],
            'empty' => ['', null, 'phone_missing'],
            'blank marker' => ['-', null, 'phone_missing'],
            'letters only' => ['abc', null, 'phone_missing'],
        ];
    }

    #[DataProvider('latitudeProvider')]
    public function test_latitude_parsing(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, FieldParser::latitude($input));
    }

    /**
     * @return array<string, array{0: mixed, 1: float|null}>
     */
    public static function latitudeProvider(): array
    {
        return [
            'trailing comma' => ['41.258827,', 41.258827],
            'trailing comma and space' => [' 41.340831, ', 41.340831],
            'decimal comma' => ['41,258827', 41.258827],
            'plain string' => ['40.999343', 40.999343],
            'float' => [41.2995, 41.2995],
            // Out of Uzbekistan's box: better no coordinate than a pin in the
            // wrong country, since the mapper has sane fallbacks.
            'longitude in the latitude column' => ['69.194217', null],
            'out of range' => ['141.5', null],
            'negative' => ['-41.2', null],
            'not a number' => ['abc', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    public function test_longitude_uses_its_own_range(): void
    {
        $this->assertSame(69.194217, FieldParser::longitude('69.194217'));
        $this->assertSame(72.236568, FieldParser::longitude(' 72.236568'));
        // A latitude value is out of the longitude range.
        $this->assertNull(FieldParser::longitude('41.2'));
    }

    #[DataProvider('priceProvider')]
    public function test_price_parsing(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, FieldParser::price($input));
    }

    /**
     * @return array<string, array{0: mixed, 1: float|null}>
     */
    public static function priceProvider(): array
    {
        return [
            'space grouped' => ['200 000', 200000.0],
            'plain' => ['200000', 200000.0],
            'decimal comma' => ['45 000,50', 45000.5],
            'anglo grouping' => ['45,000.50', 45000.5],
            'dot as thousands' => ['45.000', 45000.0],
            'with currency word' => ['1 200 000 сум', 1200000.0],
            'numeric' => [200000, 200000.0],
            // Not a price: the publisher keeps the wording and marks the
            // offer negotiable instead of inventing a figure.
            'negotiable' => ['По договору', null],
            'tiered' => ["До 20га - 200 000\nБольше 20га- 150 000", null],
            'blank marker' => ['-', null],
            'empty' => ['', null],
        ];
    }

    public function test_experience_years(): void
    {
        $this->assertSame(5.0, FieldParser::experienceYears('5'));
        $this->assertSame(5.0, FieldParser::experienceYears('5 йил'));
        $this->assertSame(10.0, FieldParser::experienceYears('10 лет'));
        $this->assertNull(FieldParser::experienceYears('-'));
        $this->assertNull(FieldParser::experienceYears('abc'));
        // Beyond a plausible working life: a typo, not a value.
        $this->assertNull(FieldParser::experienceYears('150'));
    }

    public function test_blank_markers_and_text(): void
    {
        foreach (['', '-', '—', 'нет', 'N/A'] as $marker) {
            $this->assertTrue(FieldParser::isBlankMarker($marker), $marker.' should read as blank');
        }

        $this->assertFalse(FieldParser::isBlankMarker('AGRO TOMCHI'));
        $this->assertSame('AGRO TOMCHI', FieldParser::text('  AGRO TOMCHI  '));
        $this->assertNull(FieldParser::text('-'));
        $this->assertSame('AGRO', FieldParser::text('AGRO TOMCHI', 4));
    }
}
