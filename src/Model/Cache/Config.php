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
class Config
{
    public const ALL = '*';
    public const APCU = 'apc';
    public const PHPSTATIC = 'static';
    public const DATABASE = 'database';
    /**
     * @var int
     */
    protected $default_ttl = 5 * 60;
    protected string $adaptor_name;
    protected bool $activated;

    public function __construct(
        string $adaptor_name,
        bool $activated = false
    ) {
        $this->adaptor_name = $adaptor_name;
        $this->activated = $activated;
    }

    public function getAdaptorName(): string
    {
        return $this->adaptor_name;
    }

    public function getDefaultTTL(): int
    {
        return $this->default_ttl;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }
}
