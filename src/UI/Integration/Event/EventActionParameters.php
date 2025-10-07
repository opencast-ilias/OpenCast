<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventActionParameters
{
    private array $parameters = [];

    public function with(EventActionParameter $parameter, mixed $value): self
    {
        $this->parameters[$parameter->value] = $value;
        return $this;
    }

    public function get(EventActionParameter $parameter): mixed
    {
        return $this->parameters[$parameter->value] ?? null;
    }
}
