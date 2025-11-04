<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\Data\URI;
use ILIAS\UI\Component\Modal\Modal;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Action implements \Stringable
{
    public function __construct(
        private string $name,
        private URI|Modal $target,
        private ActionType $type = ActionType::EXTERNAL_LINK
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function target(): URI|Modal
    {
        return $this->target;
    }

    /**
     * @deprecated
     */
    public function openInNewTab(): bool
    {
        return $this->type === ActionType::EXTERNAL_LINK;
    }

    public function type(): ActionType
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return (string) $this->target;
    }

}
