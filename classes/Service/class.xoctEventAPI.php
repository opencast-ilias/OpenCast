<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequestPayload;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequestPayload;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;

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
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $workflow_param_repository;

    public function __construct()
    {
        global $opencastContainer;
        $this->event_repository = $opencastContainer[EventAPIRepository::class];
        $opencastDIC = OpencastDIC::getInstance();
        $this->md_factory = $opencastDIC->metadata()->metadataFactory();
        $this->acl_utils = $opencastDIC->acl_utils();
        $this->workflow_param_repository = $opencastDIC->workflow_parameter_series_repository();
    }

    public static function getInstance(): self
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
     * @param String          $series_id
     * @param String          $title
     * @param String|DateTime $start
     * @param String|DateTime $end
     * @param String          $location
     * @param array           $additional_data
     *
     * @throws xoctException
     */
    public function create(
        string $series_id,
        string $title,
        $start,
        $end,
        string $location,
        array $additional_data = []
    ): Event {
        $metadata = $this->md_factory->event();
        $metadata->getField(MDFieldDefinition::F_IS_PART_OF)->setValue($series_id);
        $metadata->getField(MDFieldDefinition::F_TITLE)->setValue($title);
        $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->setValue(
            $additional_data['description'] ?? ''
        );
        $metadata->getField(MDFieldDefinition::F_CREATOR)->setValue(
            isset($additional_data['presenters']) ? explode(',', $additional_data['presenters']) : []
        );

        $scheduling = new Scheduling(
            $location,
            $start instanceof DateTime ? DateTimeImmutable::createFromMutable(
                $start->setTimezone(new DateTimeZone('GMT'))
            ) : new DateTimeImmutable($start),
            $end instanceof DateTime ? DateTimeImmutable::createFromMutable(
                $end->setTimezone(new DateTimeZone('GMT'))
            ) : new DateTimeImmutable($end),
            PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL)[0] == "" ? ['default'] : PluginConfig::getConfig(
                PluginConfig::F_SCHEDULE_CHANNEL
            )
        );

        $workflow_parameters = $this->workflow_param_repository->getGeneralAutomaticallySetParameters();
        if (is_array($additional_data['workflow_parameters'])) {
            $workflow_parameters += $additional_data['workflow_parameters'];
        }
        $workflow_parameters = array_map(function ($value): string {
            return $value == 1 ? 'true' : 'false';
        }, $workflow_parameters);
        $processing = new Processing(
            PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
            (object) $workflow_parameters
        );

        $acl = $this->acl_utils->getStandardRolesACL();

        $this->event_repository->schedule(
            new ScheduleEventRequest(
                new ScheduleEventRequestPayload(
                    $metadata->withoutEmptyFields(),
                    $acl,
                    $scheduling,
                    $processing
                )
            )
        );

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
     * @param array  $data
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

        $metadata = $this->md_factory->event()->withoutEmptyFields();
        $scheduling = $event->getScheduling();
        foreach ($data as $title => $value) {
            if (in_array($title, ['title', 'description', 'presenters'])) {
                // presenters is actually an MD field called creator. this is a workaround to not break compatability
                if ($title === 'presenters') {
                    $title = MDFieldDefinition::F_CREATOR;
                    $value = explode(',', $value);
                }
                $metadataField = $event->getMetadata()->getField($title);
                $metadataField->setValue($value);
                $metadata->addField($metadataField);
            } elseif ($title === 'start') {
                $scheduling->setStart(new DateTimeImmutable($data['start']));
            } elseif ($title === 'end') {
                $scheduling->setEnd(new DateTimeImmutable($data['end']));
            } elseif ($title === 'location') {
                $scheduling->setAgentId($data['location']);
            }
        }

        if ($data !== []) { // this prevents an update, if only 'online' has changed
            $this->event_repository->update(
                new UpdateEventRequest(
                    $event_id,
                    new UpdateEventRequestPayload(
                        $metadata,
                        null,
                        $scheduling
                    )
                )
            );
        }

        return $event;
    }

    public function delete(string $event_id): bool
    {
        $this->event_repository->delete($event_id);
        return true;
    }

    /**
     * @throws xoctException
     */
    public function filter(array $filter): array
    {
        return $this->event_repository->getFiltered($filter);
    }
}
