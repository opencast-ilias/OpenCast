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

namespace srag\Plugins\Opencast\Model\Cache\Adaptor;

use srag\Plugins\Opencast\Model\Cache\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Factory
{
    public function getSpecific(string $adaptor, Config $config): Adaptor
    {
        switch ($adaptor) {
            case Config::APCU:
                $adaptor = new APCu($config);
                break;
            case Config::PHPSTATIC:
                $adaptor = new PHPStatic($config);
                break;
            case Config::DATABASE:
                $adaptor = new Database($config);
                break;
        }

        return $adaptor->isAvailable() ? $adaptor : new PHPStatic($config);
    }

    public function getWithConfig(Config $config): Adaptor
    {
        return $this->getSpecific($config->getAdaptorName(), $config);
    }
}
