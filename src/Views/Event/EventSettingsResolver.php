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
    private bool $permission_per_clip;

    public function __construct(
        ObjectSettings $object_settings
    ) {
        $this->permission_per_clip = $object_settings->getPermissionPerClip();
    }

    public function resolve(EventSettings $setting): mixed
    {
        return match ($setting) {
            EventSettings::SHOW_BEST_ACTION => true,
            EventSettings::DESCRIPTION_MAX_LENGTH => 120,
            EventSettings::STATUS_MAX_LENGTH => 80,
            EventSettings::SHOW_OWNER => $this->permission_per_clip,
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
