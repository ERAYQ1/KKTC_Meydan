<?php

namespace KktcMeydan\BusinessProfile;

use Flarum\User\User;

/**
 * Isletme iletisim bilgileri (adres/telefon/whatsapp/saat) sadece "Isletme"
 * grubu uyeleri icin herkese acik olmali - aksi halde her kullanici kendi
 * profil tercihine bu alanlari yazip normal bir uyeyi isletme gibi
 * gosterebilir (ya da rastgele bir telefon/adres kamuya sizabilir).
 */
class BusinessGroupGate
{
    const GROUP_NAME_SINGULAR = 'İşletme';

    public static function isBusinessUser(User $user): bool
    {
        return $user->groups->contains(
            fn ($group) => $group->name_singular === self::GROUP_NAME_SINGULAR
        );
    }

    /**
     * Preference transformer for business contact fields - strips HTML/script
     * tags so a business profile can't inject markup into visitor browsers.
     */
    public static function sanitize($value): string
    {
        return strip_tags(trim((string) $value));
    }
}
