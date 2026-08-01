<?php

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\User\User;
use KktcMeydan\BusinessProfile\Api\Controller\CreateBusinessReviewController;
use KktcMeydan\BusinessProfile\Api\Controller\DeleteBusinessReviewController;
use KktcMeydan\BusinessProfile\Api\Controller\ListBusinessReviewsController;
use KktcMeydan\BusinessProfile\BusinessGroupGate;
use KktcMeydan\BusinessProfile\BusinessReview;
use KktcMeydan\BusinessProfile\Listener\ValidateBusinessUrls;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\User())
        ->registerPreference('business_address', 'KktcMeydan\BusinessProfile\BusinessGroupGate::sanitize', '')
        ->registerPreference('business_phone', 'KktcMeydan\BusinessProfile\BusinessGroupGate::sanitize', '')
        ->registerPreference('business_whatsapp', 'KktcMeydan\BusinessProfile\BusinessGroupGate::sanitize', '')
        ->registerPreference('business_hours', 'KktcMeydan\BusinessProfile\BusinessGroupGate::sanitize', '')
        ->registerPreference('business_map_url', 'strval', '')
        ->registerPreference('business_photo_url', 'strval', ''),

    (new Extend\Event())
        ->listen(\Flarum\User\Event\Saving::class, ValidateBusinessUrls::class),

    (new Extend\Routes('api'))
        ->get('/business-reviews', 'kktcmeydan-business-profile.reviews.index', ListBusinessReviewsController::class)
        ->post('/business-reviews', 'kktcmeydan-business-profile.reviews.create', CreateBusinessReviewController::class)
        ->delete('/business-reviews/{id}', 'kktcmeydan-business-profile.reviews.delete', DeleteBusinessReviewController::class),

    // Preferences are private to the account owner by default (only exposed
    // via CurrentUserSerializer). A business's contact info must be public
    // on their profile, so expose it as a regular attribute on every user -
    // but only for actual "İşletme" group members, otherwise any member
    // could fill these preferences and pass themselves off as a business.
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('isBusinessUser', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user);
        })
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
        })
        ->attribute('businessMapUrl', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_map_url') : null;
        })
        ->attribute('businessPhotoUrl', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? $user->getPreference('business_photo_url') : null;
        })
        ->attribute('businessReviewCount', function ($serializer, User $user) {
            return BusinessGroupGate::isBusinessUser($user) ? BusinessReview::where('business_user_id', $user->id)->count() : null;
        })
        ->attribute('businessAvgRating', function ($serializer, User $user) {
            if (! BusinessGroupGate::isBusinessUser($user)) {
                return null;
            }

            $avg = BusinessReview::where('business_user_id', $user->id)->avg('rating');

            return $avg !== null ? round((float) $avg, 1) : null;
        }),
];
