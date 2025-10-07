<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Series;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class SeriesActionParameters
{
    private array $parameters = [];

    public function with(SeriesActionParameter $parameter, string $value): self
    {
        $this->parameters[$parameter->value] = $value;
        return $this;
    }

    public function get(SeriesActionParameter $parameter): ?string
    {
        return $this->parameters[$parameter->value] ?? null;
    }
}
