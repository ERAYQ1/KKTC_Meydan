<?php

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use KktcMeydan\Classifieds\Listener\SaveClassifiedFieldsToDatabase;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Model(Discussion::class))
        ->cast('price', 'float')
        ->cast('location', 'string')
        ->cast('contact_phone', 'string')
        ->cast('classified_type', 'string'),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('price', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->price !== null ? (float) $discussion->price : null;
        })
        ->attribute('currency', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->currency;
        })
        ->attribute('location', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->location;
        })
        ->attribute('contactPhone', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->contact_phone;
        })
        ->attribute('classifiedType', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->classified_type;
        }),

    // Single source of truth for the currency/type option lists:
    // SaveClassifiedFieldsToDatabase::VALID_CURRENCIES/VALID_TYPES also
    // validate incoming data server-side, so the composer's <select>
    // options come from the exact same array instead of a second,
    // hand-kept copy in JS that could silently drift from what the server
    // actually accepts.
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('classifiedCurrencies', function () {
            return SaveClassifiedFieldsToDatabase::VALID_CURRENCIES;
        })
        ->attribute('classifiedTypes', function () {
            return SaveClassifiedFieldsToDatabase::VALID_TYPES;
        }),

    (new Extend\Event())
        ->listen(Saving::class, SaveClassifiedFieldsToDatabase::class),
];
