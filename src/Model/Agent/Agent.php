<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\Agent;

use DateTimeImmutable;

/**
 * Class xoctAgent
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Agent
{
    /**
     * @var string
     */
    private $agent_id;
    /**
     * @var string[]
     */
    private $inputs;
    /**
     * @var DateTimeImmutable
     */
    private $update;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $status;

    /**
     * @param string[] $inputs
     */
    public function __construct(string $agent_id, string $status, array $inputs, DateTimeImmutable $update, string $url)
    {
        $this->agent_id = $agent_id;
        $this->status = $status;
        $this->inputs = $inputs;
        $this->update = $update;
        $this->url = $url;
    }

    public function getAgentId(): string
    {
        return $this->agent_id;
    }

    /**
     * @return string[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getUpdate(): DateTimeImmutable
    {
        return $this->update;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
