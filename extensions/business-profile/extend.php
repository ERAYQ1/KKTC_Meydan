<?php

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\User\User;
use KktcMeydan\BusinessProfile\BusinessGroupGate;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\User())
        ->registerPreference('business_address', 'strval', '')
        ->registerPreference('business_phone', 'strval', '')
        ->registerPreference('business_whatsapp', 'strval', '')
        ->registerPreference('business_hours', 'strval', ''),

    // Preferences are private to the account owner by default (only exposed
    // via CurrentUserSerializer). A business's contact info must be public
    // on their profile, so expose it as a regular attribute on every user -
    // but only for actual "İşletme" group members, otherwise any member
    // could fill these preferences and pass themselves off as a business.
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('businessAddress', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_address') : null;
        })
        ->attribute('businessPhone', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_phone') : null;
        })
        ->attribute('businessWhatsapp', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_whatsapp') : null;
        })
        ->attribute('businessHours', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_hours') : null;
        }),
];
