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

namespace srag\Plugins\Opencast\Model\Cache\Adaptor;

use srag\Plugins\Opencast\Model\Cache\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface Adaptor
{
    public const CONTAINER_PREFIX_SEPARATOR = ':';

    public function __construct(Config $config);

    public function isAvailable(): bool;

    public function has(string $container, string $key): bool;

    public function get(string $container, string $key): ?string;

    public function set(
        string $container,
        string $key,
        string $value,
        int $ttl
    ): void;

    public function delete(string $container, string $key): void;

    public function flushContainer(string $container): void;

    public function flush(): void;
}
