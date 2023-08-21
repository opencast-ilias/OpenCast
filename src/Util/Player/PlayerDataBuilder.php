<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Event\Event;
use xoctException;
use srag\Plugins\Opencast\API\API;

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
     * @var API
     */
    protected $api;

    /**
     * @var Event
     */
    protected $event;

    /**
     * PlayerDataBuilder constructor.
     */
    public function __construct(Event $event)
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->event = $event;
    }

    /**
     * @throws xoctException
     */
    abstract public function buildStreamingData(): array;
}
