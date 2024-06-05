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

namespace srag\Plugins\Opencast\Model\Cache;

use ILIAS\Cache\Nodes\NodeRepository;
use ILIAS\Cache\Nodes\NullNodeRepository;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface Cacheable
{
    public function toCache(): array;

    public function fromCache(array $data): void;
}
