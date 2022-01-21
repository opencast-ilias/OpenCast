<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequestPayload;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequestPayload;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Scheduling\Processing;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Util\DI\OpencastDIC;

/**
 * Class xoctEventAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventAPI
{

    /**
     * @var self
     */
    protected static $instance;
    /**
     * @var EventAPIRepository
     */
    private $event_repository;
    /**
     * @var MetadataFactory
     */
    private $md_factory;
    /**
     * @var ACLUtils
     */
    private $acl_utils;

    public function __construct()
    {
        $opencastDIC = OpencastDIC::getInstance();
        $this->event_repository = $opencastDIC->event_repository();
        $this->md_factory = OpencastDIC::getInstance()->metadata()->metadataFactory();
        $this->acl_utils = OpencastDIC::getInstance()->acl_utils();
    }


    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * possible additional data:
     *
     *  description => text
     *  presenters => text
     *  workflow_parameters => array(text => int)
     *
     * @param String $series_id
     * @param String $title
     * @param String|DateTime $start
     * @param String|DateTime $end
     * @param String $location
     * @param array $additional_data
     *
     * @return Event
     * @throws xoctException
     */
    public function create(string $series_id,
                           string $title,
                                  $start,
                                  $end,
                           string $location,
                           array  $additional_data = array()
    ): Event
    {
        $metadata = $this->md_factory->event();
        $metadata->getField(MDFieldDefinition::F_IS_PART_OF)->setValue($series_id);
        $metadata->getField(MDFieldDefinition::F_TITLE)->setValue($title);
        $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->setValue(
            $additional_data['description'] ?? '');
        $metadata->getField(MDFieldDefinition::F_CREATOR)->setValue(
            isset($additional_data['presenters']) ? explode(',', $additional_data['presenters']) : []);
        $metadata->getField(MDFieldDefinition::F_LOCATION)->setValue($location);

        $scheduling = new Scheduling(
            $location,
            $start instanceof DateTime ? DateTimeImmutable::createFromMutable($start) : new DateTimeImmutable($start),
            $end instanceof DateTime ? DateTimeImmutable::createFromMutable($end) : new DateTimeImmutable($end),
        );

        $processing = new Processing(
            xoctConf::getConfig(xoctConf::F_WORKFLOW),
            (object)$additional_data['workflow_parameters']);

        $acl = $this->acl_utils->getStandardRolesACL();

        $this->event_repository->schedule(new ScheduleEventRequest(
            new ScheduleEventRequestPayload(
                $metadata->withoutEmptyFields(), $acl, $scheduling, $processing)
        ));

        $event = new Event();
        $event->setMetadata($metadata);
        $event->setAcl($acl);
        $event->setScheduling($scheduling);
        return $event;
    }

    public function read(string $event_id): Event
    {
        return $this->event_repository->find($event_id);
    }


    /**
     * possible data:
     *
     *  title => text
     *  start => date
     *  end => date
     *  location => text
     *  description => text
     *  presenters => text
     *  online => bool
     *
     * @param String $event_id
     * @param array $data
     *
     * @return Event
     */
    public function update(string $event_id, array $data): Event
    {
        $event = $this->event_repository->find($event_id);

        // field 'online' is stored in ILIAS, not in Opencast
        if (isset($data['online'])) {
            $event->getXoctEventAdditions()->setIsOnline($data['online']);
            $event->getXoctEventAdditions()->update();
            unset($data['online']);
        }

        foreach ($data as $title => $value) {
            // presenters is actually an MD field called creator. this is a workaround to not break compatability
            $title = $title === 'presenters' ? MDFieldDefinition::F_CREATOR : $title;
            $value = $value instanceof DateTime ? DateTimeImmutable::createFromMutable($value) : $value;
            $event->getMetadata()->getField($title)->setValue($value);
        }

        if (count($data)) { // this prevents an update, if only 'online' has changed
            $this->event_repository->update(new UpdateEventRequest($event_id, new UpdateEventRequestPayload($event->getMetadata())));
        }

        return $event;
    }


    public function delete($event_id) : bool
    {
        $this->event_repository->delete($event_id);
        return true;
    }


    /**
     * @param array $filter
     *
     * @return array
     * @throws xoctException
     */
    public function filter(array $filter) : array
    {
        return $this->event_repository->getFiltered($filter);
    }

}