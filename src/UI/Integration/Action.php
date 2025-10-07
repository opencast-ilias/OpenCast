<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\Data\URI;
use ILIAS\UI\Component\Signal;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Action implements \Stringable
{
    public function __construct(
        private string $name,
        private URI|Signal $target,
        private bool $open_in_new_tab = false,
        private ?array $modals = null
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function target(): URI|Signal
    {
        return $this->target;
    }

    public function openInNewTab(): bool
    {
        return $this->open_in_new_tab;
    }

    public function modals(): ?array
    {
        return $this->modals;
    }

    public function __toString(): string
    {
        return (string) $this->target;
    }

}
