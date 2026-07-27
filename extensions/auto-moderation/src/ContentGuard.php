<?php

namespace KktcMeydan\AutoModeration;

class ContentGuard
{
    // Matches a standalone 11-digit run (TC/KKTC kimlik no length) after
    // stripping separators, so "12345678901" and "1234 5678 901" both hit.
    // Candidates are then filtered by checksum + phone-prefix exclusion
    // below, since a KKTC/TR mobile or landline number written with its
    // leading 0 ("05XX XXX XX XX", "0392 XXX XX XX") is also 11 digits.
    private const ID_NUMBER_PATTERN = '/(?<!\d)\d{11}(?!\d)/';

    private const PHONE_PREFIX_PATTERN = '/^0(392|5\d{2})/';

    private const HEALTH_PRIVACY_TERMS = [
        'hiv pozitif', 'hiv+', 'aids hastası', 'kanser teşhisi', 'kanser hastası',
        'psikiyatri hastası', 'akıl hastası', 'ruhsal rahatsızlığı var', 'ruh sağlığı raporu',
        'hamileliğini gizledi', 'kürtaj oldu', 'intihar girişimi', 'madde bağımlısı',
        'cinsel yolla bulaşan', 'sağlık raporu sızdı', 'tıbbi kaydı', 'hasta dosyası',
    ];

    // Institution/professional references combined with defamatory accusation
    // words — either list alone is too noisy (both are common words on their
    // own), the *combination* in the same post is the actual signal.
    private const TARGET_TERMS = [
        'doktor', 'hekim', 'hemşire', 'eczane', 'eczacı', 'hastane', 'poliklinik',
        'belediye', 'polis', 'karakol', 'itfaiye', 'savcılık', 'mahkeme',
    ];

    private const ACCUSATION_TERMS = [
        'rüşvet', 'rüşvet aldı', 'sahtekar', 'sahtekarlık', 'yalancı', 'dolandırıcı',
        'dolandırdı', 'hırsız', 'malpraktis', 'ihmal sonucu', 'ihmalkar', 'kasıtlı hata',
        'liyakatsiz', 'rezalet yaptı', 'skandal',
    ];

    public static function containsIdNumber(string $text): bool
    {
        $digitsOnly = preg_replace('/[\s.-]+/', '', $text);

        if (! preg_match_all(self::ID_NUMBER_PATTERN, $digitsOnly, $matches)) {
            return false;
        }

        foreach ($matches[0] as $candidate) {
            if (preg_match(self::PHONE_PREFIX_PATTERN, $candidate)) {
                continue;
            }

            if (self::hasValidTcChecksum($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * TC/KKTC kimlik no checksum: digit 10 comes from the odd/even digit
     * sums of the first 9 digits, digit 11 from the sum of all first 10.
     * Filters out random 11-digit runs (phone numbers, order IDs, etc.)
     * that survived the phone-prefix exclusion above.
     */
    private static function hasValidTcChecksum(string $digits): bool
    {
        if ($digits[0] === '0') {
            return false;
        }

        $d = array_map('intval', str_split($digits));

        $oddSum = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
        $evenSum = $d[1] + $d[3] + $d[5] + $d[7];

        $digit10 = (($oddSum * 7) - $evenSum) % 10;

        if ($digit10 < 0) {
            $digit10 += 10;
        }

        if ($digit10 !== $d[9]) {
            return false;
        }

        $digit11 = array_sum(array_slice($d, 0, 10)) % 10;

        return $digit11 === $d[10];
    }

    public static function containsHealthPrivacyViolation(string $text): bool
    {
        return self::containsAny(self::normalize($text), self::HEALTH_PRIVACY_TERMS);
    }

    public static function containsDefamationAgainstTarget(string $text): bool
    {
        $normalized = self::normalize($text);

        return self::containsAny($normalized, self::TARGET_TERMS) && self::containsAny($normalized, self::ACCUSATION_TERMS);
    }

    private static function normalize(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
