<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class NullEventSettingsResolver implements EventSettingsValueResolver
{
    public function resolve(EventSettings $setting): mixed
    {
        return null;
    }

}
