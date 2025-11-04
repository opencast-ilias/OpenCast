<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettings;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventSettingsResolver implements EventSettingsValueResolver
{
    public function __construct(
        private ObjectSettings $object_settings
    ) {
    }

    public function resolve(EventSettings $setting): mixed
    {
        return match ($setting) {
            EventSettings::SHOW_OWNER => $this->object_settings->getPermissionPerClip(),
            EventSettings::LABELS_AS_GLYPHS => true,
            EventSettings::PRESENTED_METADATA => [
                EventSettingsValueResolver::MD_OWNER,
                EventSettingsValueResolver::MD_LOCATION,
                EventSettingsValueResolver::MD_DESCRITION,
                EventSettingsValueResolver::MD_PRESENTER,
            ],
            default => null
        };
    }

}
