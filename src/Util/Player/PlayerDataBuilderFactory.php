<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Traits\Singleton;

/**
 * Class PlayerDataBuilderFactory
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PlayerDataBuilderFactory
{
    use Singleton;

    public function getBuilder(Event $event) : PlayerDataBuilder
    {
        if ($event->isLiveEvent()) {
            return new LivePlayerDataBuilder($event);
        }
        if (PluginConfig::getConfig(PluginConfig::F_USE_GENERATED_STREAMING_URLS)) {
            return new SelfGeneratedURLPlayerDataBuilder($event);
        }
        return new StandardPlayerDataBuilder($event);
    }
}
