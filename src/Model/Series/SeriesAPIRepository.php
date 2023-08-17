<?php

namespace srag\Plugins\Opencast\Model\Series;

use ilException;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Cache\Cache;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequestPayload;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequestPayload;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesMetadataRequest;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\API;

class SeriesAPIRepository implements SeriesRepository
{
    public const OWN_SERIES_PREFIX = 'Eigene Serie von ';
    public const CACHE_PREFIX = 'series-';
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var ACLUtils
     */
    private $ACLUtils;
    /**
     * @var SeriesParser
     */
    private $seriesParser;
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;
    /**
     * @var MDParser
     */
    private $MDParser;
    /**
     * @var API
     */
    protected $api;

    public function __construct(
        Cache $cache,
        SeriesParser $seriesParser,
        ACLUtils $ACLUtils,
        MetadataFactory $metadataFactory,
        MDParser $MDParser
    ) {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->cache = $cache;
        $this->ACLUtils = $ACLUtils;
        $this->seriesParser = $seriesParser;
        $this->metadataFactory = $metadataFactory;
        $this->MDParser = $MDParser;
    }

    public function find(string $identifier): Series
    {
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): Series
    {
        $data = $this->api->getApi()->seriesApi->get($identifier, true);
        $data->metadata = $this->fetchMD($identifier);
        $series = $this->seriesParser->parseAPIResponse($data);
        $this->cache->set(self::CACHE_PREFIX . $series->getIdentifier(), $series);
        return $series;
    }

    /**
     * @throws xoctException
     */
    public function fetchMD(string $identifier): Metadata
    {
        $data = $this->api->getApi()->seriesApi->getMetadata($identifier) ?? [];
        return $this->MDParser->parseAPIResponseSeries($data);
    }

    public function create(CreateSeriesRequest $request): ?string
    {
        $payload = $request->getPayload()->jsonSerialize();
        $created_series = $this->api->getApi()->seriesApi->create(
            $payload['metadata'],
            $payload['acl'],
        );
        return $created_series->identifier;
    }

    /**
     * @throws xoctException
     */
    public function updateMetadata(UpdateSeriesMetadataRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();
        $this->api->getApi()->seriesApi->updateMetadata(
            $request->getIdentifier(),
            $payload['metadata']
        );

        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    /**
     * @throws xoctException
     */
    public function updateACL(UpdateSeriesACLRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();
        $this->api->getApi()->seriesApi->updateAcl(
            $request->getIdentifier(),
            $payload['acl']
        );
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    /**
     * Warning: Doesn't load all metadata, only the title, since it's currently used only for selection dropdowns
     *
     * @return array|Series[]
     * @throws xoctException
     */
    public function getAllForUser(string $user_string): array
    {
        if ($existing = $this->cache->get('series-' . $user_string)) {
            return $existing;
        }
        $return = [];
        try {
            $data = $this->api->getApi()->seriesApi->runWithRoles([$user_string])->getAll([
                'onlyWithWriteAccess' => true,
                'withacl' => true,
                'limit' => 5000
            ]);
        } catch (ilException $e) {
            return [];
        }
        foreach ($data as $d) {
            try {
                $metadata = $this->metadataFactory->series();
                $metadata->addField(
                    (new MetadataField(MDFieldDefinition::F_TITLE, MDDataType::text()))->withValue($d->title)
                );
                $d->metadata = $metadata;
                $return[] = $this->seriesParser->parseAPIResponse($d);
            } catch (xoctException $e) {    // it's possible that the current user has access to more series than the configured API user
                continue;
            }
        }
        $this->cache->set('series-' . $user_string, $return, 60);

        return $return;
    }

    public function getOrCreateOwnSeries(xoctUser $xoct_user): Series
    {
        $series = $this->getOwnSeries($xoct_user);
        if (is_null($series)) {
            $metadata = $this->metadataFactory->series();
            $metadata->getField(MDFieldDefinition::F_TITLE)->setValue($this->getOwnSeriesTitle($xoct_user));
            $this->create(
                new CreateSeriesRequest(
                    new CreateSeriesRequestPayload(
                        $metadata,
                        $this->ACLUtils->getStandardRolesACL()->merge(
                            $this->ACLUtils->getUserRolesACL($xoct_user)
                        )
                    )
                )
            );
        }
        return $series;
    }

    public function getOwnSeries(xoctUser $xoct_user): ?Series
    {
        $existing = $this->api->getApi()->seriesApi->getAll([
            'filter' => [
                'title' => $this->getOwnSeriesTitle($xoct_user)
            ],
            'withacl' => true,
        ]);
        if (empty($existing)) {
            return null;
        }
        $series = $this->seriesParser->parseAPIResponse(reset($existing));
        $series->getAccessPolicies()->merge(
            $this->ACLUtils->getUserRolesACL($xoct_user)
        );
        $this->updateACL(
            new UpdateSeriesACLRequest(
                $series->getIdentifier(),
                new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
            )
        );
        return $series;
    }

    public function getOwnSeriesTitle(xoctUser $xoct_user): string
    {
        return self::OWN_SERIES_PREFIX . $xoct_user->getLogin();
    }
}
