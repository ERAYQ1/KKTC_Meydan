<?php

namespace KktcMeydan\BusinessProfile\Listener;

use Flarum\User\Event\Saving;
use KktcMeydan\BusinessProfile\BusinessUrlValidator;

class ValidateBusinessUrls
{
    public function handle(Saving $event)
    {
        $mapUrl = (string) $event->user->getPreference('business_map_url');
        $photoUrl = (string) $event->user->getPreference('business_photo_url');

        if ($mapUrl !== '') {
            BusinessUrlValidator::assertValid($mapUrl, 'business_map_url');
        }

        if ($photoUrl !== '') {
            BusinessUrlValidator::assertValid($photoUrl, 'business_photo_url');
        }
    }
}
