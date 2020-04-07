<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use ILIAS\DI\Container;
use Metadata;
use ReflectionException;
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

        $data['metadata'] = json_encode([$event->getMetadata()->__toStdClass()]);
        $data['processing'] = json_encode($event->getProcessing());
        $data['acl'] = json_encode($event->getAcl());

        $presenter = xoctUploadFile::getInstanceFromFileArray('file_presenter');
        $data['presentation'] = $presenter->getCURLFile();
        if (xoctConf::getConfig(xoctConf::F_INGEST_UPLOAD)) {
            $this->ingest($data);
        } else {
            $return = json_decode(xoctRequest::root()->events()->post($data));
        }
        //		for ($x = 0; $x < 50; $x ++) { // Use this to upload 50 Clips at once, for testing
        //		}

        $event->setIdentifier($return->identifier);
    }


    /**
     * @param array $data
     *
     * @throws xoctException
     */
    private function ingest(array $data)
    {
        $xoctRequestSettings = new xoctRequestSettings();
        $xoctRequestSettings->setApiBase(rtrim(xoctConf::getConfig(xoctConf::F_API_BASE), '/api'));
        xoctRequest::init($xoctRequestSettings);
        $media_package = xoctRequest::root()->ingest()->createMediaPackage()->get();
        $media_package = xoctRequest::root()->ingest()->addDCCatalog()->post([
            'dublinCore' => $data['metadata'],
            'mediaPackage' => $media_package,
            'flavor' => 'dublincore/episode'
        ]);
        $media_package = xoctRequest::root()->ingest()->addAttachment()->post([
            'body' => $data['acl'],
            'mediaPackage' => $media_package,
            'flavor' => 'security/xacml+episode'
        ]);
        $media_package = xoctRequest::root()->ingest()->addTrack()->post([
            'url' => $data['presentation'],
            'mediaPackage' => $media_package,
            'flavor' => 'presentation'
        ]);
        $response = xoctRequest::root()->ingest()->ingest(xoctConf::getConfig(xoctConf::F_WORKFLOW))->post([
            'mediaPackage' => $media_package
        ]);

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
}