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
class SeriesRepository
{

    /**
     * @param xoctUser $xoct_user
     *
     * @return string
     * @throws xoctException
     */
    public function getOrCreateOwnSeries(xoctUser $xoct_user) : string
    {
        $series_title = 'Eigene Serie von ' . ilObjUser::_lookupLogin($xoct_user->getIliasUserId());
        $existing = xoctRequest::root()->series()->parameter(
            'filter',
            'title:' . $series_title
        )->get();
        $existing = json_decode($existing, true);
        if (empty($existing)) {
            $series = new xoctSeries();
            $series->setTitle($series_title);
            $std_acls = new xoctAclStandardSets();
            $series->setAccessPolicies($std_acls->getAcls());
            $series->addProducer($xoct_user, true);
            $series->create();
            return $series->getIdentifier();
        }
        return $existing[0]['identifier'];
    }

}