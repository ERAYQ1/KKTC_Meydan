<?php

/*
 * flarum/phpstan pulls in larastan, which calls Laravel's global
 * `database_path()` helper (via MigrationHelper) whenever it inspects an
 * Eloquent model - but Flarum doesn't boot the full Laravel framework, so
 * that helper is never defined and PHPStan crashes on every file touching
 * a Flarum\Database\AbstractModel subclass. Stubbed here since the actual
 * return value is never used for anything this project's PHPStan run
 * checks (no migration-path-dependent analysis).
 */
if (! function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return __DIR__.'/database'.($path !== '' ? '/'.$path : '');
    }
}
