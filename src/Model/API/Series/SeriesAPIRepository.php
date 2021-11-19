<?php

namespace srag\Plugins\Opencast\Model\API\Series;

use xoctAclStandardSets;
use xoctException;
use xoctRequest;
use xoctSeries;
use xoctUser;
use ilObjUser;

/**
 * Class SeriesRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Series
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class SeriesAPIRepository
{

    const OWN_SERIES_PREFIX = 'Eigene Serie von ';

    /**
     * @param xoctUser $xoct_user
     *
     * @return xoctSeries
     * @throws xoctException
     */
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

    /**
     * @param xoctUser $xoct_user
     * @return xoctSeries|null
     * @throws xoctException
     */
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

    /**
     * @param xoctUser $xoct_user
     * @return string
     */
    public function getOwnSeriesTitle(xoctUser $xoct_user) : string
    {
        return self::OWN_SERIES_PREFIX . $xoct_user->getLogin();
    }
}
