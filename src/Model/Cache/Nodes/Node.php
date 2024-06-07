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

namespace srag\Plugins\Opencast\Model\Cache\Nodes;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Node
{
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var int|null
     */
    private $weight;

    public function __construct(string $host, int $port, ?int $weight = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->weight = $weight;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }
}
