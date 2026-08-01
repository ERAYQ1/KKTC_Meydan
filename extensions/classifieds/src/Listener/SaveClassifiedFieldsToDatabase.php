<?php

namespace KktcMeydan\Classifieds\Listener;

use Flarum\Discussion\Event\Saving;

class SaveClassifiedFieldsToDatabase
{
    const VALID_CURRENCIES = ['TRY', 'GBP', 'USD', 'EUR'];
    const VALID_TYPES = ['satilik', 'kiralik', 'is_ilani', 'ev_arkadasi', 'ikinci_el'];

    // Must match the `discussions` column lengths (see classifieds
    // migration). With `strict` mode on, MySQL rejects an over-length
    // INSERT/UPDATE outright instead of silently truncating it - so an
    // unvalidated long value now fails the whole save rather than just
    // losing its tail.
    const LOCATION_MAX_LENGTH = 255;
    const CONTACT_PHONE_MAX_LENGTH = 32;

    public function handle(Saving $event)
    {
        $attributes = $event->data['attributes'] ?? [];
        $discussion = $event->discussion;

        if (array_key_exists('price', $attributes)) {
            $price = $attributes['price'];
            $discussion->price = ($price === null || $price === '') ? null : (float) $price;
        }

        if (array_key_exists('currency', $attributes)) {
            $currency = strtoupper((string) $attributes['currency']);
            $discussion->currency = in_array($currency, self::VALID_CURRENCIES, true) ? $currency : 'TRY';
        }

        if (array_key_exists('location', $attributes)) {
            $location = mb_substr(strip_tags(trim((string) $attributes['location'])), 0, self::LOCATION_MAX_LENGTH);
            $discussion->location = $location === '' ? null : $location;
        }

        if (array_key_exists('contactPhone', $attributes)) {
            $phone = mb_substr(strip_tags(trim((string) $attributes['contactPhone'])), 0, self::CONTACT_PHONE_MAX_LENGTH);
            $discussion->contact_phone = $phone === '' ? null : $phone;
        }

        if (array_key_exists('classifiedType', $attributes)) {
            $type = strip_tags(trim((string) $attributes['classifiedType']));
            $discussion->classified_type = in_array($type, self::VALID_TYPES, true) ? $type : null;
        }
    }
}
