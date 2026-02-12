<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettings;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventSettingsResolver implements EventSettingsValueResolver
{
    private bool $permission_per_clip;
    private bool $player_in_modal;
    private bool $show_best_action = true; // TODO make configurable
    private bool $edit_all_metadata;

    public function __construct(
        ObjectSettings $object_settings
    ) {
        $this->permission_per_clip = $object_settings->getPermissionPerClip();
        $this->player_in_modal = (bool) PluginConfig::getConfig(
            PluginConfig::F_USE_MODALS
        );
        $this->edit_all_metadata = ((int) PluginConfig::getConfig(
            PluginConfig::F_SCHEDULED_METADATA_EDITABLE
        )) === PluginConfig::ALL_METADATA;
    }

    public function resolve(EventSettings $setting): mixed
    {
        return match ($setting) {
            EventSettings::EDIT_ALL_METADATA => $this->edit_all_metadata,
            EventSettings::PLAYER_AS_MODAL => $this->player_in_modal,
            EventSettings::SHOW_BEST_ACTION => $this->show_best_action,
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
