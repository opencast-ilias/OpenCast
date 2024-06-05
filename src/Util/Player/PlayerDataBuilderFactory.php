<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;

/**
 * Class PlayerDataBuilderFactory
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PlayerDataBuilderFactory
{
    protected static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getBuilder(Event $event): PlayerDataBuilder
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
