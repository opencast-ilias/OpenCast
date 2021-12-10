<?php

namespace srag\Plugins\Opencast\Model\Scheduling;

interface SchedulingRepository
{
    public function find(string $identifier): Scheduling;
}