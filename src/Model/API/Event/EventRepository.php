<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use ILIAS\DI\Container;
use ilUtil;
use Metadata;
use ReflectionException;
use srag\Plugins\Opencast\UI\Input\Plupload;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use xoct;
use xoctConf;
use xoctEvent;
use xoctException;
use xoctRequest;
use xoctRequestSettings;
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
     * @var Container
     */
    protected $dic;


    /**
     * EventRepository constructor.
     *
     * @param Container $dic
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }


    /**
     * @param xoctEvent $event
     *
     * @throws ReflectionException
     * @throws xoctException
     */
    public function upload(xoctEvent $event)
    {
        $data = array();

        $event->setMetadata(Metadata::getSet(Metadata::FLAVOR_DUBLINCORE_EPISODES));
        $event->setOwner(xoctUser::getInstance($this->dic->user()));
        $event->updateMetadataFromFields(false);

        $presenter = xoctUploadFile::getInstanceFromFileArray('file_presenter');
        if (xoctConf::getConfig(xoctConf::F_INGEST_UPLOAD)) {
            $this->ingest($event, $presenter);
        } else {
            $data['metadata'] = json_encode([$event->getMetadata()->__toStdClass()]);
            $data['processing'] = json_encode($event->getProcessing());
            $data['acl'] = json_encode($event->getAcl());
            $data['presentation'] = $presenter->getCURLFile();
            json_decode(xoctRequest::root()->events()->post($data))->identifier;
        }
    }


    /**
     * @param xoctEvent      $event
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
    private function getACLFile(xoctEvent $event) : xoctUploadFile
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
    private function formatWorkflowParameters(array $workflow_parameters) : array
    {
        $return = [];
        foreach ($workflow_parameters as $workflow_parameter => $value) {
            $return[$workflow_parameter] = $value ? 'true' : 'false';
        }
        return $return;
    }



    /**
     * @param array  $filter
     * @param string $for_user
     * @param array  $roles
     * @param int    $offset
     * @param int    $limit
     * @param string $sort
     * @param bool   $as_object
     *
     * @return xoctEvent[] | array
     * @throws xoctException
     */
    public function getFiltered(array $filter, $for_user = '', $roles = [], $offset = 0, $limit = 1000, $sort = '', $as_object = false) {
        /**
         * @var $xoctEvent xoctEvent
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

        if (self::$load_md_separate || self::$no_metadata) {
            $request->parameter('withmetadata', false);
        } else {
            $request->parameter('withmetadata', true);
        }

        if (!self::$load_acl_separate) {
            $request->parameter('withacl', true);
        }

        if (!self::$load_pub_separate) {
            $request->parameter('withpublications', true);
        }

        if (xoct::isApiVersionGreaterThan('v1.1.0')){
            $request->parameter('withscheduling', true);
        }

        if (xoctConf::getConfig(xoctConf::F_PRESIGN_LINKS)) {
            $request->parameter('sign', true);
        }

        $data = json_decode($request->get($roles, $for_user)) ?: [];
        $return = array();

        foreach ($data as $d) {
            $xoctEvent = xoctEvent::findOrLoadFromStdClass($d->identifier, $d);
            if (!in_array($xoctEvent->getProcessingState(), [xoctEvent::STATE_SUCCEEDED, xoctEvent::STATE_OFFLINE])) {
                xoctEvent::removeFromCache($xoctEvent->getIdentifier());
            }
            $return[] = $as_object ? $xoctEvent : $xoctEvent->getArrayForTable();
        }

        return $return;
    }


    /**
     * @return string
     * @throws xoctException
     */
    private function getIngestNodeURL() : string
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
