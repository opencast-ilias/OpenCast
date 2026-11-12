<?php

declare(strict_types=1);

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
use srag\Plugins\Opencast\Container\Init;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAPIRepository implements EventRepository, Request
{
    protected API $api;
    public $opencastDIC;

    /**
     * @readonly
     */
    private Container $cache;
    /**
     * @var array<string, \stdClass> raw API data of cacheable events that getFiltered() was asked not to
     *                                cache right away, see cacheEvents()
     */
    private array $deferred_cache = [];

    public function __construct(
        Services $cache_services,
        private EventParser $eventParser,
        private OpencastIngestService $ingestService
    ) {
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
        $this->cache = $cache_services->get($this);
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
                'withmetadata' => false, // Setting this to true would cause a huge performance decrease
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
        $this->cache->delete($identifier);
        foreach (PermissionGrant::where(['event_identifier' => $identifier])->get() as $invitation) {
            $invitation->delete();
        }
        return true;
    }

    public function invalidateCache(string $identifier): void
    {
        $this->cache->delete($identifier);
    }

    /**
     * @throws xoctException
     */
    public function upload(UploadEventRequest $request): void
    {
        // If there are subtitles or thumbnails to be uploaded alongside the video upload, we have to use ingest upload.
        if (
            PluginConfig::getConfig(PluginConfig::F_INGEST_UPLOAD)
            || $request->getPayload()->hasThumbnail()
            || $request->getPayload()->hasSubtitles()
        ) {
            $this->ingestService->ingest($request);
        } else {
            $serialized_payload = $request->getPayload()->jsonSerialize();

            $presenter = null;
            $presentation = $request->getPayload()->getPresentation()->getFileStream();

            if ($request->getPayload()->hasAudioFile()) {
                $presentation = null;
                $presenter = $request->getPayload()->getPresenter()->getFileStream();
            }

            $audio = null;
            $response = $this->api->routes()->eventsApi->create(
                $serialized_payload['acl'],
                $serialized_payload['metadata'],
                $serialized_payload['processing'],
                '', // Scheduling (here must be empty string)
                $presenter,
                $presentation,
                $audio
            );
        }
    }

    /**
     * @return Event[]|string[][]
     */
    public function getFiltered(
        array $filter,
        string $for_user = '',
        array $roles = [],
        int $offset = 0,
        int $limit = 1000,
        string $sort = '',
        bool $as_object = false,
        bool $cache_events = true
    ): array {
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
        // nmake sure we have proper values here
        $data = array_filter(
            (array) $this->api->routes()->eventsApi->runWithRoles($roles)->runAsUser($for_user)->getAll($params),
            static fn($event): bool => $event instanceof \stdClass
        );
        $return = [];

        $this->opencastDIC = Init::init()->legacy();

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
                if ($cache_events) {
                    $this->cache->set($d->identifier, $d);
                } else {
                    $this->deferred_cache[$d->identifier] = $d;
                }
            }
        }

        return $return;
    }

    /**
     * Writes events to the cache that a getFiltered() call with $cache_events = false left out. A listing
     * fetches a whole series but shows a single page, so only the events on that page - the ones a user
     * is about to open - are worth a cache write.
     */
    public function cacheEvents(Event ...$events): void
    {
        foreach ($events as $event) {
            $identifier = $event->getIdentifier();
            if (isset($this->deferred_cache[$identifier])) {
                $this->cache->set($identifier, $this->deferred_cache[$identifier]);
                unset($this->deferred_cache[$identifier]);
            }
        }
    }

    /**
     * Whether the series holds at least one event in one of the given states (e.g. scheduled). The answer
     * is cached for the cache's default lifetime, so a newly scheduled event may take that long to show.
     *
     * @param string[] $states
     */
    public function seriesHasEventInState(string $series_id, array $states): bool
    {
        $key = 'series_has_state_' . md5($series_id . '|' . implode(',', $states));
        if ($this->cache->has($key)) {
            return (bool) $this->cache->get($key);
        }

        $found = false;
        foreach ($states as $state) {
            if ($this->getFiltered(
                ['is_part_of' => $series_id, 'status' => $state],
                limit: 1,
                as_object: true,
                cache_events: false
            ) !== []) {
                $found = true;
                break;
            }
        }

        $this->cache->set($key, $found);
        return $found;
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
