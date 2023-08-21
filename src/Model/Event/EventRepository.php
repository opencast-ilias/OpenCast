<?php

namespace srag\Plugins\Opencast\Model\Event;

use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use xoctException;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
interface EventRepository
{
    public function find(string $identifier): Event;

    public function delete(string $identifier): bool;

    /**
     * @throws xoctException
     */
    public function upload(UploadEventRequest $request): void;

    /**
     * @param string $for_user
     * @param array  $roles
     * @param int    $offset
     * @param int    $limit
     * @param string $sort
     * @param bool   $as_object
     *
     * @return Event[] | array
     * @throws xoctException
     */
    public function getFiltered(
        array $filter,
        $for_user = '',
        $roles = [],
        $offset = 0,
        $limit = 1000,
        $sort = '',
        $as_object = false
    );

    public function update(UpdateEventRequest $request): void;

    public function schedule(ScheduleEventRequest $request): string;

    public function updateACL(UpdateEventRequest $request): void;
}
