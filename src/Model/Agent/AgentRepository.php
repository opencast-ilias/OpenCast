<?php

namespace srag\Plugins\Opencast\Model\Agent;

use Exception;

interface AgentRepository
{
    /**
     * @return Agent[]
     * @throws Exception
     */
    public function findAll(): array;
}