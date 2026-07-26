<?php

namespace KktcMeydan\AutoModeration;

class ContentGuard
{
    // Matches a standalone 11-digit run (TC/KKTC kimlik no length) after
    // stripping separators, so "12345678901" and "1234 5678 901" both hit.
    private const ID_NUMBER_PATTERN = '/(?<!\d)\d{11}(?!\d)/';

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
        return (bool) preg_match(self::ID_NUMBER_PATTERN, preg_replace('/[\s.-]+/', '', $text));
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
