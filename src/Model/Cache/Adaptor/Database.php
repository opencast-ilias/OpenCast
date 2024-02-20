<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Cache\Adaptor;

use srag\Plugins\Opencast\Model\Cache\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Database implements Adaptor
{
    private const TABLE_NAME = 'xoct_cache';
    public const KEY_COMBINER = '_';
    /**
     * @var \ilDBInterface
     */
    private $db;

    public function __construct(Config $config)
    {
        global $DIC;
        $this->db = $DIC->database();

        // truncate old entries once per request
        $this->db->manipulateF(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE expires < %s',
            ['integer'],
            [time()]
        );
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function has(string $container, string $key): bool
    {
        $combined_key = $container . self::KEY_COMBINER . $key;
        return $this->db->queryF(
            'SELECT identifier  FROM ' . self::TABLE_NAME . ' WHERE identifier = %s AND expires > %s',
            ['text', 'integer'],
            [$combined_key, time()]
        )->rowCount() > 0;
    }

    public function get(string $container, string $key): ?string
    {
        $combined_key = $container . self::KEY_COMBINER . $key;
        $result = $this->db->queryF(
            'SELECT value  FROM ' . self::TABLE_NAME . ' WHERE identifier = %s AND expires > %s',
            ['text', 'integer'],
            [$combined_key, time()]
        );
        $row = $this->db->fetchAssoc($result);
        return $row['value'] ?? null;
    }

    public function set(string $container, string $key, string $value, int $ttl): void
    {
        $combined_key = $container . self::KEY_COMBINER . $key;
        if ($this->has($container, $key)) {
            $this->db->update(
                self::TABLE_NAME,
                [
                    'value' => ['text', $value],
                    'expires' => ['integer', time() + $ttl],
                ],
                [
                    'identifier' => ['text', $combined_key],
                ]
            );
            return;
        }

        $this->db->insert(
            self::TABLE_NAME,
            [
                'identifier' => ['text', $combined_key],
                'value' => ['text', $value],
                'expires' => ['integer', time() + $ttl],
            ]
        );
    }

    public function delete(string $container, string $key): void
    {
        $combined_key = $container . self::KEY_COMBINER . $key;
        $this->db->manipulateF(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE identifier = %s',
            ['text'],
            [$combined_key]
        );
    }

    public function flushContainer(string $container): void
    {
        $this->db->manipulateF(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE identifier LIKE %s',
            ['text'],
            [$container . '_%']
        );
    }

    public function flush(): void
    {
        $this->db->manipulate('DELETE FROM ' . self::TABLE_NAME);
    }
}
