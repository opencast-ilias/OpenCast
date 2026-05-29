<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event;

use ilDBInterface;

/**
 * Persistence for {@see EventAdditions}, keyed per (event, ILIAS object).
 *
 * Replaces the former EventAdditionsAR ActiveRecord. Uses ilDB directly so the
 * online state can be stored with a composite key (id, obj_id).
 */
class EventAdditionsRepository
{
    public const TABLE_NAME = 'xoct_event_additions';

    public function __construct(private readonly ilDBInterface $db)
    {
    }

    /**
     * Returns the stored additions for the given event in the given ILIAS object.
     * Defaults to "online" when nothing has been stored yet.
     */
    public function find(string $event_id, int $obj_id): EventAdditions
    {
        if ($event_id === '' || $event_id === '0') {
            return new EventAdditions($event_id, $obj_id, true);
        }

        $result = $this->db->queryF(
            'SELECT is_online FROM ' . self::TABLE_NAME . ' WHERE id = %s AND obj_id = %s',
            ['text', 'integer'],
            [$event_id, $obj_id]
        );
        $row = $this->db->fetchAssoc($result);
        if ($row === null || $row === false) {
            return new EventAdditions($event_id, $obj_id, true);
        }

        return new EventAdditions($event_id, $obj_id, (bool) $row['is_online']);
    }

    public function store(EventAdditions $additions): void
    {
        if ($additions->getEventId() === '' || $additions->getEventId() === '0') {
            return;
        }

        $this->db->replace(
            self::TABLE_NAME,
            [
                'id' => ['text', $additions->getEventId()],
                'obj_id' => ['integer', $additions->getObjId()],
            ],
            [
                'is_online' => ['integer', $additions->getIsOnline() ? 1 : 0],
            ]
        );
    }
}
