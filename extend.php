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
];
