<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use ilObjUser;
use Opis\Closure\SerializableClosure;
use ReflectionException;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\API\ACL\ACL;
use srag\Plugins\Opencast\Model\API\ACL\ACLRepository;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataRepository;
use srag\Plugins\Opencast\Model\API\Publication\PublicationRepository;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\MetadataDIC;
use srag\Plugins\Opencast\UI\Input\Plupload;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use stdClass;
use xoct;
use xoctConf;
use xoctEvent;
use xoctEventAdditions;
use xoctException;
use xoctInvitation;
use xoctRequest;
use xoctUploadFile;
use xoctUser;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventRepository
{

    public static $load_md_separate = true;
    public static $load_acl_separate = false;
    public static $load_pub_separate = true;
    public static $no_metadata = false;

    /**
     * @var Cache
     */
    protected $cache;
    /**
     * @var MDParser
     */
    protected $md_parser;
    /**
     * @var MetadataRepository
     */
    protected $md_repository;
    /**
     * @var ACLRepository
     */
    protected $acl_repository;
    /**
     * @var PublicationRepository
     */
    protected $publication_repository;


    public function __construct(Cache                  $cache,
                                MetadataDIC            $metadataDIC,
                                ?ACLRepository         $acl_repository = null,
                                ?PublicationRepository $publication_repository = null)
    {
        $this->cache = $cache;
        $this->md_parser = $metadataDIC->metadataParser();
        $this->md_repository = $metadataDIC->metadataRepository();
        $this->acl_repository = $acl_repository ?? new ACLRepository($cache);
        $this->publication_repository = $publication_repository ?? new PublicationRepository($cache);
    }

    public function find(string $identifier): xoctEvent
    {
        return $this->cache->get('event-' . $identifier)
            ?? $this->fetch($identifier);
    }

    private function fetch(string $identifier): xoctEvent
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->get());
        $event = $this->buildEventFromStdClass($data, $identifier);
        $this->cache->set('event-' . $event->getIdentifier(), $event);
        return $event;
    }

    public function delete(string $identifier) : bool
    {
        xoctRequest::root()->events($identifier)->delete();
        foreach (xoctInvitation::where(array('event_identifier' => $identifier))->get() as $invitation) {
            $invitation->delete();
        }
        return true;
    }

    /**
     * @param stdClass $data
     * @param string $identifier
     * @return xoctEvent
     * @throws xoctException
     */
    private function buildEventFromStdClass(stdClass $data, string $identifier): xoctEvent
    {
        $event = new xoctEvent();
        $event->setPublicationStatus($data->publication_status);
        $event->setStatus($data->status);
        $event->setHasPreviews($data->has_previews);
        $event->setXoctEventAdditions(xoctEventAdditions::findOrGetInstance($identifier));

        if (isset($data->metadata)) {
            $event->setMetadata($this->md_parser->parseAPIResponseEvent($data->metadata));
        } else {
            // lazy loading
            $event->setMetadataReference(new SerializableClosure(function () use ($identifier) {
                return $this->md_repository->find($identifier);
            }));
        }

        if (isset($data->acl)) {
            $event->setAcl(ACL::fromResponse($data->acl));
        } else {
            // lazy loading
            $event->setAclReference(new SerializableClosure(function () use ($identifier) {
                return $this->acl_repository->find($identifier);
            }));
        }

        if (isset($data->publications)) {
            $event->publications()->loadFromArray($data->publications);
        } else {
            // lazy loading
            $event->publications()->setReference(new SerializableClosure(function () use ($identifier) {
                return $this->publication_repository->find($identifier);
            }));
        }
        return $event;
    }


    /**
     * @param xoctEvent $event
     * @param ilObjUser $owner
     * @throws ReflectionException
     * @throws xoctException
     */
    public function upload(xoctEvent $event, ilObjUser $owner)
    {
        $data = array();

        $event->setMetadata(new Metadata());
        $event->setOwner(xoctUser::getInstance($owner));
        $event->updateMetadataFromFields(false);

        $presenter = xoctUploadFile::getInstanceFromFileArray('file_presenter');
        if (xoctConf::getConfig(xoctConf::F_INGEST_UPLOAD)) {
            $this->ingest($event, $presenter);
        } else {
            $data['metadata'] = json_encode([$event->getMetadata()->__toStdClass()]);
            $data['processing'] = json_encode($event->getProcessing());
            $data['acl'] = json_encode($event->getAcl()->getEntries());
            $data['presentation'] = $presenter->getCURLFile();
            json_decode(xoctRequest::root()->events()->post($data))->identifier;
        }
    }


    /**
     * @param xoctEvent $event
     * @param xoctUploadFile $presentation
     *
     * @throws xoctException
     */
    private function ingest(xoctEvent $event, xoctUploadFile $presentation)
    {
        $ingest_node_url = $this->getIngestNodeURL();

        // create media package
        $media_package = xoctRequest::root()->ingest()->createMediaPackage()->get([], '', $ingest_node_url);

        // Metadata
        $media_package = xoctRequest::root()->ingest()->addDCCatalog()->post([
            'dublinCore' => (new MetadataToXML($event->getMetadata()))->getXML(),
            'mediaPackage' => $media_package,
            'flavor' => 'dublincore/episode'
        ], [], '', $ingest_node_url);

        // ACLs (as attachment)
        $media_package = xoctRequest::root()->ingest()->addAttachment()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'security/xacml+episode'
        ], [$this->getACLFile($event)], [], '', $ingest_node_url);

        // track
        $media_package = xoctRequest::root()->ingest()->addTrack()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'presentation/source'
        ], [$presentation], [], '', $ingest_node_url);

        // ingest
        $post_params = [
            'mediaPackage' => $media_package,
            'workflowDefinitionId' => xoctConf::getConfig(xoctConf::F_WORKFLOW)
        ];
        $post_params = array_merge($post_params, $this->formatWorkflowParameters($event->getWorkflowParameters()));
        xoctRequest::root()->ingest()->ingest()->post($post_params, [], '', $ingest_node_url);
    }


    /**
     * @param xoctEvent $event
     *
     * @return xoctUploadFile
     */
    private function getACLFile(xoctEvent $event): xoctUploadFile
    {
        $plupload = new Plupload();
        $tmp_name = uniqid('tmp');
        file_put_contents($plupload->getTargetDir() . '/' . $tmp_name, (new ACLtoXML($event->getAcl()))->getXML());
        $upload_file = new xoctUploadFile();
        $upload_file->setFileSize(filesize($plupload->getTargetDir() . '/' . $tmp_name));
        $upload_file->setPostVar('attachment');
        $upload_file->setTitle('attachment');
        $upload_file->setTmpName($tmp_name);
        return $upload_file;
    }

    /**
     * format workflow parameters to send it to the workflow rest api endpoint
     *
     * @param array $workflow_parameters
     *
     * @return array
     */
    private function formatWorkflowParameters(array $workflow_parameters): array
    {
        $return = [];
        foreach ($workflow_parameters as $workflow_parameter => $value) {
            $return[$workflow_parameter] = $value ? 'true' : 'false';
        }
        return $return;
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
     * @return xoctEvent[] | array
     * @throws xoctException
     */
    public function getFiltered(array $filter, $for_user = '', $roles = [], $offset = 0, $limit = 1000, $sort = '', $as_object = false)
    {
        /**
         * @var $event xoctEvent
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
            if (!in_array($event->getProcessingState(), [xoctEvent::STATE_SUCCEEDED, xoctEvent::STATE_OFFLINE])) {
                xoctEvent::removeFromCache($event->getIdentifier());
            }
            $return[] = $as_object ? $event : $event->getArrayForTable();
        }

        return $return;
    }

    /**
     * @return string
     * @throws xoctException
     */
    private function getIngestNodeURL(): string
    {
        $nodes = json_decode(xoctRequest::root()->services()->available('org.opencastproject.ingest')->get(), true);
        if (!is_array($nodes)
            || !isset($nodes['services'])
            || !isset($nodes['services']['service'])
            || empty($nodes['services']['service'])
        ) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        $available_hosts = [];
        $services = $nodes['services']['service'];
        $services = isset($services['type']) ? [$services] : $services; // only one service?
        foreach ($services as $node) {
            if ($node['active'] && $node['host']) {
                $available_hosts[] = $node['host'];
            }
        }
        if (count($available_hosts) === 0) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        return array_rand(array_flip($available_hosts));
    }
}
