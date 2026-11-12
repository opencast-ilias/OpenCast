<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event;

/**
 * Metadata of an Event that is stored only in ILIAS, scoped per ILIAS object.
 *
 * Immutable value object. Persistence is handled by {@see EventAdditionsRepository}.
 *
 * The online/offline state is kept per (event, ILIAS object) so that the same Opencast
 * series linked in several ILIAS objects can have different online states.
 */
final class EventAdditions
{
    public function __construct(
        private readonly string $event_id,
        private readonly int $obj_id,
        private readonly bool $is_online = true
    ) {
    }

    public function getEventId(): string
    {
        return $this->event_id;
    }

    public function getObjId(): int
    {
        return $this->obj_id;
    }

    public function getIsOnline(): bool
    {
        return $this->is_online;
    }

    public function withIsOnline(bool $is_online): self
    {
        return new self($this->event_id, $this->obj_id, $is_online);
    }
}
