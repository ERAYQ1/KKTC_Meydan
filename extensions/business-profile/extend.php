<?php

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\User\User;

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
    // on their profile, so expose it as a regular attribute on every user.
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('businessAddress', function ($serializer, User $user) {
            return $user->getPreference('business_address');
        })
        ->attribute('businessPhone', function ($serializer, User $user) {
            return $user->getPreference('business_phone');
        })
        ->attribute('businessWhatsapp', function ($serializer, User $user) {
            return $user->getPreference('business_whatsapp');
        })
        ->attribute('businessHours', function ($serializer, User $user) {
            return $user->getPreference('business_hours');
        }),
];
