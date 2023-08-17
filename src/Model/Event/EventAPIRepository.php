<?php

namespace srag\Plugins\Opencast\Model\Event;

use srag\Plugins\Opencast\Model\Cache\Cache;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Util\FileTransfer\OpencastIngestService;
use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\DI\OpencastDIC;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAPIRepository implements EventRepository
{
    public $opencastDIC;
    public const CACHE_PREFIX = 'event-';

    /**
     * @var Cache
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
        Cache $cache,
        EventParser $eventParser,
        OpencastIngestService $ingestService
    ) {
        $this->cache = $cache;
        $this->ingestService = $ingestService;
        $this->eventParser = $eventParser;
    }

    public function find(string $identifier): Event
    {
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): Event
    {
        $data = OpencastAPI::getApi()->eventsApi->get(
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
        if (in_array($event->getProcessingState(), [Event::STATE_SUCCEEDED, Event::STATE_OFFLINE])) {
            $this->cache->set(self::CACHE_PREFIX . $event->getIdentifier(), $event);
        }
        return $event;
    }

    public function delete(string $identifier): bool
    {
        OpencastAPI::getApi()->eventsApi->delete($identifier);
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
            $response = OpencastAPI::getApi()->eventsApi->create(
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
        $data = OpencastAPI::getApi()->eventsApi->runWithRoles($roles)->runAsUser($for_user)->getAll($params);
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
                $this->cache->set(self::CACHE_PREFIX . $event->getIdentifier(), $event);
            }
        }

        return $return;
    }

    public function update(UpdateEventRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();

        $response = OpencastAPI::getApi()->eventsApi->update(
            $request->getIdentifier(),
            $payload['acl'] ?? '',
            $payload['metadata'] ?? '',
            $payload['processing'] ?? '',
            $payload['scheduling'] ?? '',
        );
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    public function schedule(ScheduleEventRequest $request): string
    {
        $payload = $request->getPayload()->jsonSerialize();
        $response = OpencastAPI::getApi()->eventsApi->create(
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
        OpencastAPI::getApi()->eventsApi->updateAcl($request->getIdentifier(), $payload['acl']);
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }
}
