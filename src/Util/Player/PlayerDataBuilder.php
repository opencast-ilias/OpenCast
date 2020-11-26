<?php

namespace srag\Plugins\Opencast\Util\Player;

use xoctEvent;
use xoctException;
use xoctAttachment;
use xoctConf;
use Metadata;
use DateTime;
use DateTimeZone;
use xoctSecureLink;

/**
 * Class StreamingDataBuilder
 * @package srag\Plugins\Opencast\Util\StreamingData
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class PlayerDataBuilder
{
    const ROLE_MASTER = "presenter";
    const ROLE_SLAVE = "presentation";

    /**
     * @var xoctEvent
     */
    protected $event;

    /**
     * PlayerDataBuilder constructor.
     * @param xoctEvent $event
     */
    public function __construct(xoctEvent $event)
    {
        $this->event = $event;
    }

    /**
     * @return array
     * @throws xoctException
     */
    public abstract function buildStreamingData() : array;


}
