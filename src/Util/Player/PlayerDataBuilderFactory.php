<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Traits\Singleton;
use xoctConf;

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
        if (xoctConf::getConfig(xoctConf::F_USE_STREAMING)) {
            return new StreamingPlayerDataBuilder($event);
        }
        return new StandardPlayerDataBuilder($event);
    }
}
