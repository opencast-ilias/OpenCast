<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series;

use ilException;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
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
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Model\Cache\Container\Container;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Container\Init;

class SeriesAPIRepository implements SeriesRepository, Request
{
    public const OWN_SERIES_PREFIX = 'Eigene Serie von ';
    private Container $cache;
    private ACLUtils $ACLUtils;
    private SeriesParser $seriesParser;
    private MetadataFactory $metadataFactory;
    private MDParser $md_parser;
    protected API $api;

    public function __construct(
        Services $cache,
        SeriesParser $seriesParser,
        ACLUtils $ACLUtils,
        MetadataFactory $metadataFactory,
        MDParser $MDParser
    ) {
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
        $this->cache = $cache->get($this);
        $this->ACLUtils = $ACLUtils;
        $this->seriesParser = $seriesParser;
        $this->metadataFactory = $metadataFactory;
        $this->md_parser = $MDParser;
    }

    public function getContainerKey(): string
    {
        return 'series';
    }

    public function find(string $identifier): Series
    {
        return $this->fetch($identifier);
    }

    public function fetch(string $identifier): Series
    {
        if ($this->cache->has($identifier)) {
            $data = $this->cache->get($identifier);
        } else {
            $data = $this->api->routes()->seriesApi->get($identifier, true);
            $this->cache->set($identifier, $data);
        }

        $data->metadata = $this->fetchMD($identifier);
        return $this->seriesParser->parseAPIResponse($data);
    }

    /**
     * @throws xoctException
     */
    public function fetchMD(string $identifier): Metadata
    {
        $key = $identifier . '_md';
        if ($this->cache->has($key)) {
            $data = $this->cache->get($key);
        } else {
            $data = $this->api->routes()->seriesApi->getMetadata($identifier) ?? [];
            $this->cache->set($key, $data);
        }
        return $this->md_parser->parseAPIResponseSeries($data);
    }

    public function create(CreateSeriesRequest $request): ?string
    {
        $payload = $request->getPayload()->jsonSerialize();
        $created_series = $this->api->routes()->seriesApi->create(
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
        $this->api->routes()->seriesApi->updateMetadata(
            $request->getIdentifier(),
            $payload['metadata']
        );

        $this->cache->delete($request->getIdentifier());
    }

    /**
     * @throws xoctException
     */
    public function updateACL(UpdateSeriesACLRequest $request): void
    {
        $payload = $request->getPayload()->jsonSerialize();
        $this->api->routes()->seriesApi->updateAcl(
            $request->getIdentifier(),
            $payload['acl']
        );
        $this->cache->delete($request->getIdentifier());
    }

    /**
     * Warning: Doesn't load all metadata, only the title, since it's currently used only for selection dropdowns
     *
     * @return array|Series[]
     * @throws xoctException
     */
    public function getAllForUser(string $user_string): array
    {
        if ($this->cache->has($user_string)) {
            $data = $this->cache->get($user_string);
        } else {
            try {
                $data = (array) $this->api->routes()->seriesApi->runWithRoles([$user_string])->getAll([
                    'onlyWithWriteAccess' => true,
                    'withacl' => true,
                    'limit' => 5000
                ]);
                $data = array_filter($data, static function ($series) {
                    return $series instanceof \stdClass;
                });

            } catch (ilException $e) {
                $data = [];
            }
        }

        $this->cache->set($user_string, $data);
        $return = [];
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
        $existing = $this->api->routes()->seriesApi->getAll([
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
