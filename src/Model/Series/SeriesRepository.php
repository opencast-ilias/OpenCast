<?php

namespace srag\Plugins\Opencast\Model\Series;


use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use xoctException;
use xoctSeries;
use xoctUser;

/**
 * Class SeriesRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Series
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
interface SeriesRepository
{
    public function find(string $identifier) : xoctSeries;

    public function fetch(string $identifier) : xoctSeries;

    /**
     * @param CreateSeriesRequest $request
     * @return string series identifier
     */
    public function create(CreateSeriesRequest $request) : string;

    /**
     * @return xoctSeries[]
     */
    public function getAllForUser(string $user_string): array;

    /**
     * @param xoctUser $xoct_user
     *
     * @return xoctSeries
     * @throws xoctException
     */
    public function getOrCreateOwnSeries(xoctUser $xoct_user): xoctSeries;

    /**
     * @param xoctUser $xoct_user
     * @return xoctSeries|null
     * @throws xoctException
     */
    public function getOwnSeries(xoctUser $xoct_user);

    /**
     * @param xoctUser $xoct_user
     * @return string
     */
    public function getOwnSeriesTitle(xoctUser $xoct_user): string;
}