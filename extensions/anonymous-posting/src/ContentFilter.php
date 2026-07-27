<?php

namespace KktcMeydan\AnonymousPosting;

class ContentFilter
{
    // Anchored against a per-token digit buffer (see containsPhoneNumber),
    // never the whole text, so unrelated digits scattered across a message
    // ("oda 5 kat 39 daire 22") can't accidentally concatenate into a match.
    private const PHONE_DIGITS_PATTERN = '/^(90)?0?(392|5\d{2})\d{6,7}$/';

    private const PROFANITY_WORDS = [
        'amk', 'amına koyayım', 'amına koyim', 'siktir', 'siktir git', 'siktiğim',
        'orospu', 'orospu çocuğu', 'oç', 'piç', 'piç kurusu', 'yavşak', 'yavsak',
        'ibne', 'göt herif', 'got herif', 'gerizekalı', 'gerizekali', 'pezevenk',
        'kahpe', 'şerefsiz', 'serefsiz', 'dallama', 'angut', 'gavat', 'mal herif',
        'aq', 'sik', 'yarrak', 'yarrağı', 'göt lale',
    ];

    private const LEETSPEAK_MAP = [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
        '5' => 's', '7' => 't', '8' => 'b',
    ];

    private const TURKISH_ASCII_FOLD_MAP = [
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
    ];

    public static function containsPhoneNumber(string $text): bool
    {
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $digitBuffer = '';

        foreach ($tokens as $token) {
            $isDigitShaped = (bool) preg_match('/^[\d().+\-]+$/', $token);

            if (! $isDigitShaped) {
                $digitBuffer = '';

                continue;
            }

            $digitBuffer .= preg_replace('/\D+/', '', $token);

            if (preg_match(self::PHONE_DIGITS_PATTERN, $digitBuffer)) {
                return true;
            }

            // Run has outgrown any valid phone length without matching -
            // it's not a phone number, drop it rather than let it grow
            // forever (e.g. long ID/order-number strings split by spaces).
            if (strlen($digitBuffer) > 12) {
                $digitBuffer = '';
            }
        }

        return false;
    }

    public static function containsProfanity(string $text): bool
    {
        $normalized = self::normalize($text);

        foreach (self::PROFANITY_WORDS as $word) {
            // (?<!\p{L}) instead of \b: \b's unicode support is unreliable
            // across PCRE builds for Turkish letters. Trailing \p{L}* (not
            // \b) lets Turkish agglutinative suffixes attach directly to the
            // root ("orospusun", "amk'yım") and still match.
            $pattern = '/(?<!\p{L})'.preg_quote(self::normalize($word), '/').'\p{L}*/u';

            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Strip stray combining marks (e.g. U+0307 COMBINING DOT ABOVE left
        // behind by some clients decomposing "İ" -> "i" + dot), which would
        // otherwise split a word mid-token and dodge matching.
        $text = preg_replace('/\p{Mn}/u', '', $text);

        // Leetspeak evasion ("s1ktir", "4mk", "0rospu") before the ASCII
        // fold below, so digit-substituted letters land on the same
        // normalized form as the (also folded) word list.
        $text = strtr($text, self::LEETSPEAK_MAP);

        // Fold Turkish letters to ASCII look-alikes so accent-stripped
        // evasion ("siktir" vs "sıktır") matches the same normalized form
        // as the word list.
        $text = strtr($text, self::TURKISH_ASCII_FOLD_MAP);

        return preg_replace('/\s+/u', ' ', trim($text));
    }
}
