<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Event\Event;
use xoctException;

/**
 * Class StreamingDataBuilder
 * @package srag\Plugins\Opencast\Util\StreamingData
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class PlayerDataBuilder
{
    public const ROLE_MASTER = "presenter";
    public const ROLE_SLAVE = "presentation";

    /**
     * @var Event
     */
    protected $event;

    /**
     * PlayerDataBuilder constructor.
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return array
     * @throws xoctException
     */
    abstract public function buildStreamingData(): array;
}
