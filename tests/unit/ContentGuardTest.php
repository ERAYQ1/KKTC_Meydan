<?php

namespace KktcMeydan\Tests\unit;

use KktcMeydan\AutoModeration\ContentGuard;
use PHPUnit\Framework\TestCase;

class ContentGuardTest extends TestCase
{
    /** @dataProvider idNumberProvider */
    public function test_contains_id_number(string $text, bool $expected): void
    {
        $this->assertSame($expected, ContentGuard::containsIdNumber($text));
    }

    public function idNumberProvider(): array
    {
        return [
            'valid tc checksum' => ['kimlik no 10000000146', true],
            'invalid checksum random digits' => ['siparis no 12345678901', false],
            'leading zero never valid' => ['01000000014', false],
            'mobile number with leading 0 excluded' => ['beni 05331234567 ara', false],
            'landline with leading 0 excluded' => ['0392 228 1234', false],
            'no digits' => ['bu gayet normal bir mesaj', false],
        ];
    }
}
