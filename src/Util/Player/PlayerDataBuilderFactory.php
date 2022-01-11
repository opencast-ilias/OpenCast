<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Traits\Singleton;
use xoctEvent;
use xoctConf;

/**
 * Class PlayerDataBuilderFactory
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PlayerDataBuilderFactory
{
    use Singleton;

    public function getBuilder(xoctEvent $event) : PlayerDataBuilder
    {
        if ($event->isLiveEvent()) {
            return new LivePlayerDataBuilder($event);
        }
        if (xoctConf::getConfig(xoctConf::F_USE_GENERATED_STREAMING_URLS)) {
            return new SelfGeneratedURLPlayerDataBuilder($event);
        }
        return new StandardPlayerDataBuilder($event);
    }
}
