<?php

use Flarum\Extend;
use Flarum\Post\Event\Saving;
use KktcMeydan\AutoModeration\Listener\GuardRegulatedCategoryContent;

return [
    (new Extend\Event())
        ->listen(Saving::class, GuardRegulatedCategoryContent::class),
];
