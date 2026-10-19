<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util;

use ilSetting;

/**
 * Remembers whether the last contact with Opencast ran into a connection failure, meaning no
 * HTTP response at all or a proxy reporting the Opencast behind it as down.
 *
 * The repository list view needs this information for every single object it renders. Asking
 * Opencast once per object would multiply the loading time of a course page exactly while
 * Opencast is down, so the state is written whenever a regular API call fails to reach the
 * server and cleared as soon as any API call succeeds again. Reading it costs one cached
 * ilSetting lookup.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class OpencastAvailability
{
    private const MODULE = 'xoct';
    private const KEY_UNREACHABLE_SINCE = 'unreachable_since';

    private static ?ilSetting $settings = null;

    public static function rememberUnreachable(): void
    {
        // Keep the first timestamp: it marks the beginning of the outage, not the last attempt.
        if (self::settings()->get(self::KEY_UNREACHABLE_SINCE) === null) {
            self::settings()->set(self::KEY_UNREACHABLE_SINCE, (string) time());
        }
    }

    public static function rememberReachable(): void
    {
        if (self::settings()->get(self::KEY_UNREACHABLE_SINCE) !== null) {
            self::settings()->delete(self::KEY_UNREACHABLE_SINCE);
        }
    }

    public static function isUnreachable(): bool
    {
        return self::settings()->get(self::KEY_UNREACHABLE_SINCE) !== null;
    }

    private static function settings(): ilSetting
    {
        return self::$settings ??= new ilSetting(self::MODULE);
    }
}
