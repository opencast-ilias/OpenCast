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
use xoctRequest;
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
        $data = json_decode(xoctRequest::root()->events($identifier)
            ->parameter('withmetadata', true)
            ->parameter('withacl', true)
            ->parameter('withpublications', true)
            ->parameter('withscheduling', true)
            ->parameter('sign', (bool) PluginConfig::getConfig(PluginConfig::F_PRESIGN_LINKS))
            ->get(), null, 512, JSON_THROW_ON_ERROR);
        $event = $this->eventParser->parseAPIResponse($data, $identifier);
        if (in_array($event->getProcessingState(), [Event::STATE_SUCCEEDED, Event::STATE_OFFLINE])) {
            $this->cache->set(self::CACHE_PREFIX . $event->getIdentifier(), $event);
        }
        return $event;
    }

    public function delete(string $identifier): bool
    {
        xoctRequest::root()->events($identifier)->delete();
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
            json_decode(xoctRequest::root()->events()
                ->post($request->getPayload()->jsonSerialize()), null, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @param array $filter
     * @param string $for_user
     * @param array $roles
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @param bool $as_object
     *
     * @return Event[] | array
     * @throws xoctException
     */
    public function getFiltered(array $filter, $for_user = '', $roles = [], $offset = 0, $limit = 1000, $sort = '', $as_object = false)
    {
        /**
         * @var $event Event
         */
        $request = xoctRequest::root()->events();
        if ($filter) {
            $filter_string = '';
            foreach ($filter as $k => $v) {
                $filter_string .= $k . ':' . $v . ',';
            }
            $filter_string = rtrim($filter_string, ',');

            $request->parameter('filter', $filter_string);
        }

        $request->parameter('offset', $offset);
        $request->parameter('limit', $limit);

        if ($sort) {
            $request->parameter('sort', $sort);
        }

        $request->parameter('withmetadata', false)
            ->parameter('withacl', true)
            ->parameter('withpublications', true)
            ->parameter('withscheduling', true)
            ->parameter('sign', (bool) PluginConfig::getConfig(PluginConfig::F_PRESIGN_LINKS));

        $data = json_decode($request->get($roles, $for_user), null, 512, JSON_THROW_ON_ERROR) ?: [];
        $return = [];

        $this->opencastDIC = OpencastDIC::getInstance();

        foreach ($data as $d) {
            $event = $this->eventParser->parseAPIResponse($d, $d->identifier);

            if ($as_object === true) {
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
        xoctRequest::root()->events($request->getIdentifier())
            ->post($request->getPayload()->jsonSerialize());
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    public function schedule(ScheduleEventRequest $request): string
    {
        $response = json_decode(xoctRequest::root()->events()->post($request->getPayload()->jsonSerialize()), null, 512, JSON_THROW_ON_ERROR);
        return is_array($response) ? $response[0]->identifier : $response->identifier;
    }

    public function updateACL(UpdateEventRequest $request): void
    {
        xoctRequest::root()->events($request->getIdentifier())
            ->acl()->put($request->getPayload()->jsonSerialize());
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }
}
