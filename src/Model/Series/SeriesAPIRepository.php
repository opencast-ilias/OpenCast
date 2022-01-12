<?php

namespace srag\Plugins\Opencast\Model\Series;

use ilException;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Metadata\MetadataRepository;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequestPayload;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesRequestPayload;
use xoctException;
use xoctRequest;
use xoctUser;

class SeriesAPIRepository implements SeriesRepository
{

    const OWN_SERIES_PREFIX = 'Eigene Serie von ';
    const CACHE_PREFIX = 'series-';
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
     * @var MetadataRepository
     */
    private $metadataRepository;
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(Cache              $cache,
                                SeriesParser       $seriesParser,
                                ACLUtils           $ACLUtils,
                                MetadataRepository $metadataRepository,
                                MetadataFactory    $metadataFactory)
    {
        $this->cache = $cache;
        $this->ACLUtils = $ACLUtils;
        $this->seriesParser = $seriesParser;
        $this->metadataRepository = $metadataRepository;
        $this->metadataFactory = $metadataFactory;
    }

    public function find(string $identifier): Series
    {
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): Series
    {
        $data = json_decode(xoctRequest::root()->series($identifier)->parameter('withacl', true)->get());
        $data->metadata = $this->metadataRepository->findSeriesMD($identifier);
        $series = $this->seriesParser->parseAPIResponse($data, $identifier);
        $this->cache->set(self::CACHE_PREFIX . $series->getIdentifier(), $series);
        return $series;
    }

    public function create(CreateSeriesRequest $request): string
    {
        $response = json_decode(xoctRequest::root()->series()->post($request->getPayload()->jsonSerialize()));
        return $response->identifier;
    }

    /**
     * @param UpdateSeriesRequest $request
     * @return void
     * @throws xoctException
     */
    public function update(UpdateSeriesRequest $request): void
    {
        xoctRequest::root()->series($request->getIdentifier())->metadata()
            ->put($request->getPayload()->jsonSerialize());
    }

    public function getAllForUser(string $user_string): array
    {
        if ($existing = $this->cache->get('series-' . $user_string)) {
            return $existing;
        }
        $return = array();
        try {
            $data = (array)json_decode(xoctRequest::root()->series()->parameter('limit', 5000)->get(array($user_string)));
        } catch (ilException $e) {
            return [];
        }
        foreach ($data as $d) {
            $obj = new Series();
            try {
                $obj->loadFromStdClass($d);
                $return[] = $obj;
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
                    $this->ACLUtils->getUserRolesACL($xoct_user))
            )));
        }
        return $series;
    }

    public function getOwnSeries(xoctUser $xoct_user) : ?Series
    {
        $existing = xoctRequest::root()->series()->parameter(
            'filter',
            'title:' . $this->getOwnSeriesTitle($xoct_user)
        )->get();
        $existing = json_decode($existing, true);
        if (empty($existing)) {
            return null;
        }
        $series = $this->find($existing[0]['identifier']);
        $series->getAccessPolicies()->merge(
            $this->ACLUtils->getUserRolesACL($xoct_user)
        );
        $this->update(new UpdateSeriesRequest($series->getIdentifier(),
            new UpdateSeriesRequestPayload(null, $series->getAccessPolicies())));
        return $series;
    }

    public function getOwnSeriesTitle(xoctUser $xoct_user): string
    {
        return self::OWN_SERIES_PREFIX . $xoct_user->getLogin();
    }
}
