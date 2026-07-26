<?php

/*
 * Site-local customizations for KKTC Meydan.
 * Loaded automatically by Flarum (see Flarum\Foundation\Site::loadExtenders).
 */

namespace KktcMeydan;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Extend;
use Flarum\Foundation\AbstractServiceProvider;

class NullAnnouncementsFetcher extends AnnouncementsFetcher
{
    public function fetch(): array
    {
        return [];
    }
}

class DisableAnnouncementsServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->bind(AnnouncementsFetcher::class, NullAnnouncementsFetcher::class);
    }
}

return [
    (new Extend\ServiceProvider())
        ->register(DisableAnnouncementsServiceProvider::class),

    // Turkish translations for third-party fof/* and ianm/* packages, which
    // only ship English locale files. Flarum merges every registered
    // Locales directory into the same 'tr' catalogue, so this overlay just
    // adds the missing keys (see locale-overrides/tr.yml for details).
    (new Extend\Locales(__DIR__.'/locale-overrides')),
];
