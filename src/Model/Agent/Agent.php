<?php

declare(strict_types=1);

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
     * @param string[] $inputs
     */
    public function __construct(private readonly string $agent_id, private readonly string $status, private readonly array $inputs, private readonly \DateTimeImmutable $update, private readonly string $url)
    {
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
