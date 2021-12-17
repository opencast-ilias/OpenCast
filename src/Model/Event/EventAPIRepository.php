<?php

namespace srag\Plugins\Opencast\Model\Event;

use Opis\Closure\SerializableClosure;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\ACL\ACLApiRepository;
use srag\Plugins\Opencast\Model\ACL\ACLRepository;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventACLRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Model\Metadata\MetadataAPIRepository;
use srag\Plugins\Opencast\Model\Metadata\MetadataRepository;
use srag\Plugins\Opencast\Model\Publication\PublicationAPIRepository;
use srag\Plugins\Opencast\Model\Publication\PublicationRepository;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingRepository;
use srag\Plugins\Opencast\Util\Upload\OpencastIngestService;
use xoct;
use xoctConf;
use xoctException;
use xoctInvitation;
use xoctRequest;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAPIRepository implements EventRepository
{
    const CACHE_PREFIX = 'event-';

    public static $load_md_separate = true;
    public static $load_acl_separate = false;
    public static $load_pub_separate = true;

    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var MetadataRepository
     */
    private $md_repository;
    /**
     * @var ACLRepository
     */
    private $acl_repository;
    /**
     * @var PublicationAPIRepository
     */
    private $publication_repository;
    /**
     * @var OpencastIngestService|null
     */
    private $ingestService;
    /**
     * @var SchedulingRepository
     */
    private $scheduling_repository;
    /**
     * @var EventParser
     */
    private $eventParser;


    public function __construct(Cache                 $cache,
                                EventParser           $eventParser,
                                MetadataRepository    $md_repository,
                                OpencastIngestService $ingestService,
                                ACLRepository         $acl_repository,
                                PublicationRepository $publication_repository,
                                SchedulingRepository  $scheduling_repository)
    {
        $this->cache = $cache;
        $this->md_repository = $md_repository;
        $this->ingestService = $ingestService;
        $this->acl_repository = $acl_repository;
        $this->publication_repository = $publication_repository;
        $this->scheduling_repository = $scheduling_repository;
        $this->eventParser = $eventParser;
    }

    public function find(string $identifier): Event
    {
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): Event
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->get());
        $event = $this->eventParser->parseAPIResponse($data, $identifier);
        $this->setReferences($event);
        if (in_array($event->getProcessingState(), [Event::STATE_SUCCEEDED, Event::STATE_OFFLINE])) {
            $this->cache->set(self::CACHE_PREFIX . $event->getIdentifier(), $event);
        }
        return $event;
    }

    public function delete(string $identifier): bool
    {
        xoctRequest::root()->events($identifier)->delete();
        foreach (xoctInvitation::where(array('event_identifier' => $identifier))->get() as $invitation) {
            $invitation->delete();
        }
        return true;
    }

    private function setReferences(Event $event)
    {
        $event->setMetadataReference(new SerializableClosure(function () use ($identifier) {
            return $this->md_repository->find($identifier);
        }));
        $event->setAclReference(new SerializableClosure(function () use ($identifier) {
            return $this->acl_repository->find($identifier);
        }));
        $event->publications()->setReference(new SerializableClosure(function () use ($identifier) {
            return $this->publication_repository->find($identifier);
        }));
        $event->setSchedulingReference(new SerializableClosure(function () use ($identifier) {
            return $this->scheduling_repository->find($identifier);
        }));
    }

    /**
     * @throws xoctException
     */
    public function upload(UploadEventRequest $request): void
    {
        if (xoctConf::getConfig(xoctConf::F_INGEST_UPLOAD)) {
            $this->ingestService->ingest($request);
        } else {
            json_decode(xoctRequest::root()->events()
                ->post($request->getPayload()->jsonSerialize()));
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

        if (!self::$load_md_separate) {
            $request->parameter('withmetadata', true);
        }

        if (!self::$load_acl_separate) {
            $request->parameter('withacl', true);
        }

        if (!self::$load_pub_separate) {
            $request->parameter('withpublications', true);
        }

        if (xoct::isApiVersionGreaterThan('v1.1.0')) {
            $request->parameter('withscheduling', true);
        }

        if (xoctConf::getConfig(xoctConf::F_PRESIGN_LINKS)) {
            $request->parameter('sign', true);
        }

        $data = json_decode($request->get($roles, $for_user)) ?: [];
        $return = array();

        foreach ($data as $d) {
            $event = $this->buildEventFromStdClass($d, $d->identifier);
            $return[] = $as_object ? $event : $event->getArrayForTable();
        }

        return $return;
    }

    public function update(UpdateEventRequest $request): void
    {
        xoctRequest::root()->events($request->getIdentifier())
            ->post($request->getPayload()->jsonSerialize());
        // todo: caching is not good
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
        $this->cache->delete(MetadataAPIRepository::CACHE_PREFIX . $request->getIdentifier());
    }

    public function schedule(ScheduleEventRequest $request): string
    {
        $response = json_decode(xoctRequest::root()->events()->post($request->getPayload()->jsonSerialize()));
        return is_array($response) ? $response[0]->identifier : $response->identifier;
    }

    public function updateACL(UpdateEventACLRequest $request): void
    {
        xoctRequest::root()->events($request->getIdentifier())
            ->acl()->post($request->getPayload()->jsonSerialize());
        // todo: caching is not good
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
        $this->cache->delete(ACLApiRepository::CACHE_PREFIX . $request->getIdentifier());
    }
}
