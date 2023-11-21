<?php

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

namespace srag\Plugins\Opencast\Model\Cache\Container;

use srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor;
use srag\Plugins\Opencast\Model\Cache\Config;
use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\ByTrying;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface Container
{
    /**
     * Locks the container for a given amount of seconds (max 300), in this time, get() will return null and has() will return false.
     * @throws \InvalidArgumentException if $seconds is greater than 300 or less than 0
     */
    public function lock(float $seconds): void;

    /**
     * Returns true if the container is locked
     */
    public function isLocked(): bool;

    /**
     * Returns true if the container contains a value for the given key
     */
    public function has(string $key): bool;

    /**
     * Returns the value for the given key, or null if the key does not exist
     * @return string|int|mixed[]|bool|null
     */
    public function get(string $key);

    /**
     * Sets the value for the given key
     * @param string|int|mixed[]|bool|null $value
     */
    public function set(string $key, $value): void;

    /**
     * Deletes the value for the given key
     */
    public function delete(string $key): void;

    /**
     * Deletes all values in the container
     */
    public function flush(): void;

    /**
     * Returns the name of the adaptop used (such as apc, memcache, phpstatic)
     */
    public function getAdaptorName(): string;

    /**
     * Returns the name of the container
     */
    public function getContainerName(): string;
}
