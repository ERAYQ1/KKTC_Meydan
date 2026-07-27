<?php

namespace KktcMeydan\AdsManager;

use Flarum\Foundation\ValidationException;

/**
 * Admin-only rota, ama "admin" tarayici JS'iyle uyumlu bir istemci demek
 * degil - dogrudan API'ye atilan bir istekle `javascript:`/`data:` semali
 * bir `target_url`/`image_url` her ziyaretcinin tarayicisinda calisirdi
 * (banner bir <a>/<img> olarak render ediliyor). http(s) disindaki her sema
 * reddedilir.
 */
class AdUrlValidator
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
