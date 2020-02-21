<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use ILIAS\DI\Container;
use xoct;
use xoctEvent;
use xoctException;
use xoctRequest;

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