<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Event\Event;
use xoctException;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class StreamingDataBuilder
 * @package srag\Plugins\Opencast\Util\StreamingData
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class PlayerDataBuilder
{
    public const ROLE_MASTER = "presenter";
    public const ROLE_SLAVE = "presentation";
    protected API $api;

    /**
     * PlayerDataBuilder constructor.
     */
    public function __construct(protected Event $event)
    {
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
    }

    /**
     * @throws xoctException
     */
    abstract public function buildStreamingData(): array;
}
