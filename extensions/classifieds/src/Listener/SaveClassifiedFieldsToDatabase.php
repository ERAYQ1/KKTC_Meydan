<?php

namespace KktcMeydan\Classifieds\Listener;

use Flarum\Discussion\Event\Saving;

class SaveClassifiedFieldsToDatabase
{
    const VALID_CURRENCIES = ['TRY', 'GBP', 'USD', 'EUR'];
    const VALID_TYPES = ['satilik', 'kiralik', 'is_ilani', 'ev_arkadasi', 'ikinci_el'];

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
            $location = trim((string) $attributes['location']);
            $discussion->location = $location === '' ? null : $location;
        }

        if (array_key_exists('contactPhone', $attributes)) {
            $phone = trim((string) $attributes['contactPhone']);
            $discussion->contact_phone = $phone === '' ? null : $phone;
        }

        if (array_key_exists('classifiedType', $attributes)) {
            $type = $attributes['classifiedType'];
            $discussion->classified_type = in_array($type, self::VALID_TYPES, true) ? $type : null;
        }
    }
}
