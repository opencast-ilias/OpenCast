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

namespace srag\Plugins\Opencast\Model\Cache\Nodes;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class NullNodeRepository implements NodeRepository
{
    public function store(Node $node): Node
    {
        return $node;
    }

    public function getNodes(): array
    {
        return [];
    }

    public function create(string $host, int $port, int $weight): Node
    {
        return new Node($host, $port, $weight);
    }

    public function deleteAll(): void
    {
    }
}
