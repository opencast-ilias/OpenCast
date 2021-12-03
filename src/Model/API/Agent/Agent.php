<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\API\Agent;

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
     * @param string $agent_id
     * @param string[] $inputs
     * @param DateTimeImmutable $update
     * @param string $url
     * @param string $status
     */
    public function __construct(string $agent_id, string $status, array $inputs, DateTimeImmutable $update, string $url)
    {
        $this->agent_id = $agent_id;
        $this->status = $status;
        $this->inputs = $inputs;
        $this->update = $update;
        $this->url = $url;
    }

    /**
     * @return string
     */
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

    /**
     * @return DateTimeImmutable
     */
    public function getUpdate(): DateTimeImmutable
    {
        return $this->update;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}