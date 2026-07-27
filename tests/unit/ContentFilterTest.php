<?php

namespace KktcMeydan\Tests\unit;

use KktcMeydan\AnonymousPosting\ContentFilter;
use PHPUnit\Framework\TestCase;

class ContentFilterTest extends TestCase
{
    /** @dataProvider phoneNumberProvider */
    public function test_contains_phone_number(string $text, bool $expected): void
    {
        $this->assertSame($expected, ContentFilter::containsPhoneNumber($text));
    }

    public function phoneNumberProvider(): array
    {
        return [
            'space-grouped mobile' => ['beni ara 0533 123 45 67 lutfen', true],
            'dashed with country code' => ['+90 392-228-1234', true],
            'plain digits' => ['05331234567', true],
            'unrelated digits scattered across sentence' => ['oda 5 kat 39 daire no 22 blok 81 giris 3', false],
            'no digits' => ['bu gayet normal bir mesaj', false],
        ];
    }

    /** @dataProvider profanityProvider */
    public function test_contains_profanity(string $text, bool $expected): void
    {
        $this->assertSame($expected, ContentFilter::containsProfanity($text));
    }

    public function profanityProvider(): array
    {
        return [
            'base word' => ['siktir git burdan', true],
            'turkish suffix on root' => ['orospusun sen', true],
            'ascii-fold evasion' => ['sıktır', true],
            'leetspeak digit evasion' => ['s1ktir git', true],
            'combining dot evasion' => ["sik\u{0307}tir", true],
            'clean text' => ['bugun hava cok guzel', false],
        ];
    }
}
