<?php

namespace Tests\Unit\Services;

use App\Services\WhatsApp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsAppTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function numbers(): array
    {
        return [
            'local 08 prefix' => ['081234567890', '6281234567890'],
            'with spaces and dashes' => ['0812-3456 7890', '6281234567890'],
            'international plus' => ['+62 812 3456 7890', '6281234567890'],
            'already normalised' => ['6281234567890', '6281234567890'],
            'missing leading zero' => ['81234567890', '6281234567890'],
            'empty input' => ['', null],
            'null input' => [null, null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalizes_indonesian_numbers_to_international_format(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, WhatsApp::normalize($input));
    }

    public function test_displays_normalised_number_in_local_grouped_format(): void
    {
        $this->assertSame('0812-3456-7890', WhatsApp::display('6281234567890'));
    }
}
