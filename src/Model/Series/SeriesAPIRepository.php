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
use xoctRequest;

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

    public function __construct(
        Cache $cache,
        SeriesParser $seriesParser,
        ACLUtils $ACLUtils,
        MetadataFactory $metadataFactory,
        MDParser $MDParser
    )
    {
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
        $data = json_decode(xoctRequest::root()->series($identifier)->parameter('withacl', true)->get());
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
        $data = json_decode(xoctRequest::root()->series($identifier)->metadata()->get()) ?? [];
        return $this->MDParser->parseAPIResponseSeries($data);
    }

    public function create(CreateSeriesRequest $request): ?string
    {
        $response = json_decode(xoctRequest::root()->series()->post($request->getPayload()->jsonSerialize()));
        return $response->identifier;
    }

    /**
     * @param UpdateSeriesMetadataRequest $request
     * @return void
     * @throws xoctException
     */
    public function updateMetadata(UpdateSeriesMetadataRequest $request): void
    {
        xoctRequest::root()->series($request->getIdentifier())->metadata()->parameter('type', 'dublincore/series')
            ->put($request->getPayload()->jsonSerialize());
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    /**
     * @param UpdateSeriesACLRequest $request
     * @return void
     * @throws xoctException
     */
    public function updateACL(UpdateSeriesACLRequest $request): void
    {
        xoctRequest::root()->series($request->getIdentifier())->acl()
            ->put($request->getPayload()->jsonSerialize());
        $this->cache->delete(self::CACHE_PREFIX . $request->getIdentifier());
    }

    /**
     * Warning: Doesn't load all metadata, only the title, since it's currently used only for selection dropdowns
     *
     * @param string $user_string
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
            $data = (array)json_decode(xoctRequest::root()->series()->parameter('limit', 5000)->parameter('withacl', true)->get([$user_string]));
        } catch (ilException $e) {
            return [];
        }
        foreach ($data as $d) {
            try {
                $metadata = $this->metadataFactory->series();
                $metadata->addField((new MetadataField(MDFieldDefinition::F_TITLE, MDDataType::text()))->withValue($d->title));
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
            $this->create(new CreateSeriesRequest(new CreateSeriesRequestPayload(
                $metadata,
                $this->ACLUtils->getStandardRolesACL()->merge(
                    $this->ACLUtils->getUserRolesACL($xoct_user)
                )
            )));
        }
        return $series;
    }

    public function getOwnSeries(xoctUser $xoct_user): ?Series
    {
        $existing = xoctRequest::root()->series()->parameter(
            'filter',
            'title:' . $this->getOwnSeriesTitle($xoct_user)
        )->parameter('withacl', 'true')->get();
        $existing = json_decode($existing, true);
        if (empty($existing)) {
            return null;
        }
        $series = $this->seriesParser->parseAPIResponse((object) $existing[0]);
        $series->getAccessPolicies()->merge(
            $this->ACLUtils->getUserRolesACL($xoct_user)
        );
        $this->updateACL(new UpdateSeriesACLRequest(
            $series->getIdentifier(),
            new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
        ));
        return $series;
    }

    public function getOwnSeriesTitle(xoctUser $xoct_user): string
    {
        return self::OWN_SERIES_PREFIX . $xoct_user->getLogin();
    }
}
