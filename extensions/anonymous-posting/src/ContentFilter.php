<?php

namespace KktcMeydan\AnonymousPosting;

class ContentFilter
{
    // KKTC/TR mobile prefixes (5xx) and the KKTC landline area code (392),
    // matched against a digit-only version of the text so spacing/dashes/
    // parentheses in "0533 123 45 67" or "+90 392-228-1234" can't dodge it.
    private const PHONE_DIGITS_PATTERN = '/(90)?0?(392|5\d{2})\d{6,7}/';

    private const PROFANITY_WORDS = [
        'amk', 'amına koyayım', 'amına koyim', 'siktir', 'siktir git', 'siktiğim',
        'orospu', 'orospu çocuğu', 'oç', 'piç', 'piç kurusu', 'yavşak', 'yavsak',
        'ibne', 'göt herif', 'got herif', 'gerizekalı', 'gerizekali', 'pezevenk',
        'kahpe', 'şerefsiz', 'serefsiz', 'dallama', 'angut', 'gavat', 'mal herif',
        'aq', 'sik', 'yarrak', 'yarrağı', 'göt lale',
    ];

    public static function containsPhoneNumber(string $text): bool
    {
        $digits = preg_replace('/\D+/', '', $text);

        return (bool) preg_match(self::PHONE_DIGITS_PATTERN, $digits);
    }

    public static function containsProfanity(string $text): bool
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        foreach (self::PROFANITY_WORDS as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/u', $normalized)) {
                return true;
            }
        }

        return false;
    }
}
