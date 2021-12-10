<?php

namespace srag\Plugins\Opencast\Model\Series;

use ilException;
use srag\Plugins\Opencast\Cache\Cache;
use xoctAclStandardSets;
use xoctException;
use xoctRequest;
use xoctSeries;
use xoctUser;

class SeriesAPIRepository implements SeriesRepository
{

    const OWN_SERIES_PREFIX = 'Eigene Serie von ';
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }


    public function getAllForUser($user_string) : array
    {
        if ($existing = $this->cache->get('series-' . $user_string)) {
            return $existing;
        }
        $return = array();
        try {
            $data = (array) json_decode(xoctRequest::root()->series()->parameter('limit', 5000)->get(array($user_string )));
        } catch (ilException $e) {
            return [];
        }
        foreach ($data as $d) {
            $obj = new xoctSeries();
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

    public function getOrCreateOwnSeries(xoctUser $xoct_user) : xoctSeries
    {
        $xoctSeries = $this->getOwnSeries($xoct_user);
        if (is_null($xoctSeries)) {
            $xoctSeries = new xoctSeries();
            $xoctSeries->setTitle($this->getOwnSeriesTitle($xoct_user));
            $std_acls = new xoctAclStandardSets();
            $xoctSeries->setAccessPolicies($std_acls->getAcl());
            $xoctSeries->addProducer($xoct_user, true);
            $xoctSeries->create();
        }
        return $xoctSeries;
    }

    public function getOwnSeries(xoctUser $xoct_user) /*: ?xoctSeries*/
    {
        $existing = xoctRequest::root()->series()->parameter(
            'filter',
            'title:' . $this->getOwnSeriesTitle($xoct_user)
        )->get();
        $existing = json_decode($existing, true);
        if (empty($existing)) {
            return null;
        }
        $xoctSeries = xoctSeries::find($existing[0]['identifier']);
        $xoctSeries->addProducer($xoct_user);
        return $xoctSeries;
    }

    public function getOwnSeriesTitle(xoctUser $xoct_user) : string
    {
        return self::OWN_SERIES_PREFIX . $xoct_user->getLogin();
    }
}
