<?php

namespace KktcMeydan\BusinessProfile;

use Flarum\Foundation\ValidationException;

/**
 * Harita/fotograf linkleri isletme profilinde herkese acik <a>/<img> olarak
 * render edilecek - javascript:/data: gibi semalari engelle.
 */
class BusinessUrlValidator
{
    private const ALLOWED_SCHEMES = ['http', 'https'];

    public static function assertValid(string $url, string $field): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException([$field => 'Gecerli bir URL giriniz.']);
        }

        if (! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), self::ALLOWED_SCHEMES, true)) {
            throw new ValidationException([$field => 'URL sadece http:// veya https:// ile baslayabilir.']);
        }
    }
}
