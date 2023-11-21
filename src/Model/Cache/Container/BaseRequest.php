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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class BaseRequest implements Request
{
    /**
     * @var string
     */
    private $container_key;
    /**
     * @var bool
     */
    private $forced = false;
    public function __construct(string $container_key, bool $forced = false)
    {
        $this->container_key = $container_key;
        $this->forced = $forced;
    }

    public function getContainerKey(): string
    {
        return $this->container_key;
    }

    public function isForced(): bool
    {
        return $this->forced;
    }
}
