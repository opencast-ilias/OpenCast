<?php

namespace srag\Plugins\Opencast\Model\Event;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Util\FileTransfer\OpencastIngestService;
use xoctException;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Model\Cache\Container\Container;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAPIRepository implements EventRepository, Request
{
    /**
     * @var API
     */
    protected $api;
    public $opencastDIC;

    /**
     * @var Container
     */
    private $cache;
    /**
     * @var OpencastIngestService|null
     */
    private $ingestService;
    /**
     * @var EventParser
     */
    private $eventParser;

    public function __construct(
        Services $cache_services,
        EventParser $eventParser,
        OpencastIngestService $ingestService
    ) {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->cache = $cache_services->get($this);
        $this->ingestService = $ingestService;
        $this->eventParser = $eventParser;
    }

    public function getContainerKey(): string
    {
        return 'event';
    }


    public function find(string $identifier): Event
    {
        return $this->fetch($identifier);
    }

    public function fetch(string $identifier): Event
    {
        if ($this->cache->has($identifier)) {
            $data = $this->cache->get($identifier);
            return $this->eventParser->parseAPIResponse($data, $identifier);
        }

        $data = $this->api->routes()->eventsApi->get(
            $identifier,
            [
                'withmetadata' => true,
                'withacl' => true,
                'withpublications' => true,
                'withscheduling' => true,
                'sign' => (bool) PluginConfig::getConfig(PluginConfig::F_PRESIGN_LINKS),
            ]
        );
        $event = $this->eventParser->parseAPIResponse($data, $identifier);
        if (in_array($event->getProcessingState(), [Event::STATE_SUCCEEDED, Event::STATE_OFFLINE], true)) {
            $this->cache->set($event->getIdentifier(), $data);
        }
        return $event;
    }

    public function delete(string $identifier): bool
    {
        $this->api->routes()->eventsApi->delete($identifier);
        foreach (PermissionGrant::where(['event_identifier' => $identifier])->get() as $invitation) {
            $invitation->delete();
        }
        return true;
    }

    /**
     * @throws xoctException
     */
    public function upload(UploadEventRequest $request): void
    {
        if (PluginConfig::getConfig(PluginConfig::F_INGEST_UPLOAD)) {
            $this->ingestService->ingest($request);
        } else {
            $payload = $request->getPayload()->jsonSerialize();
            $presenter = null;
            $presentation = $request->getPayload()->getPresentation()->getFileStream();
            $audio = null;
            $response = $this->api->routes()->eventsApi->create(
                $payload['acl'],
                $payload['metadata'],
                $payload['processing'],
                '', // Scheduling (here must be empty string)
                $presenter,
                $presentation,
                $audio
            );
        }
    }

    /**
     * @param string $for_user
     * @param array  $roles
     * @param int    $offset
     * @param int    $limit
     * @param string $sort
     * @param bool   $as_object
     *
     * @return \srag\Plugins\Opencast\Model\Event\Event[]|mixed[][]
     * @throws xoctException
     */
    public function getFiltered(array $filter, $for_user = '', $roles = [], $offset = 0, $limit = 1000, $sort = '', $as_object = false)
    {
        $params = [
            'withmetadata' => false,
            'withacl' => true,
            'withpublications' => true,
            'withscheduling' => true,
            'sign' => (bool) PluginConfig::getConfig(PluginConfig::F_PRESIGN_LINKS),
            'offset' => $offset,
            'limit' => $limit,
        ];
        if (!empty($filter)) {
            $params['filter'] = $filter;
        }
        if (!empty($sort)) {
            $params['sort'] = $sort;
        }
        $data = $this->api->routes()->eventsApi->runWithRoles($roles)->runAsUser($for_user)->getAll($params);
        $return = [];

        $this->opencastDIC = OpencastDIC::getInstance();

        foreach ($data as $d) {
            $event = $this->eventParser->parseAPIResponse($d, $d->identifier);

            if ($as_object) {
                $return[] = $event;
            } else {
                $array_for_table = $event->getArrayForTable();
                $array_for_table['owner_username'] = $this->opencastDIC->acl_utils()->getOwnerUsernameOfEvent($event);
                $return[] = $array_for_table;
            }

            if (in_array($event->getProcessingState(), [Event::STATE_SUCCEEDED, Event::STATE_OFFLINE], true)) {
                $this->cache->set($d->identifier, $d);
            }
        }

        return $return;
    }

    public function update(UpdateEventRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();

        $response = $this->api->routes()->eventsApi->update(
            $request->getIdentifier(),
            $payload['acl'] ?? '',
            $payload['metadata'] ?? '',
            $payload['processing'] ?? '',
            $payload['scheduling'] ?? '',
        );
        $this->cache->delete($request->getIdentifier());
    }

    public function schedule(ScheduleEventRequest $request): string
    {
        $payload = $request->getPayload()->jsonSerialize();
        $response = $this->api->routes()->eventsApi->create(
            $payload['acl'],
            $payload['metadata'],
            $payload['processing'],
            $payload['scheduling']
        );
        return is_array($response) ? $response[0]->identifier : $response->identifier;
    }

    public function updateACL(UpdateEventRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();
        $this->api->routes()->eventsApi->updateAcl($request->getIdentifier(), $payload['acl']);
        $this->cache->delete($request->getIdentifier());
    }
}
