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

namespace srag\Plugins\Opencast\Model\Cache;

use srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor;
use srag\Plugins\Opencast\Model\Cache\Adaptor\AvailableAdaptors;
use srag\Plugins\Opencast\Model\Cache\Adaptor\Factory;
use srag\Plugins\Opencast\Model\Cache\Container\ActiveContainer;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Model\Cache\Container\Container;
use srag\Plugins\Opencast\Model\Cache\Container\VoidContainer;
use srag\Plugins\Opencast\API\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Services
{
    /**
     * @var ActiveContainer[]
     */
    private $containers = [];
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor|null
     */
    private $adaptor;
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Adaptor\Factory
     */
    private $adaptor_factory;
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Config
     */
    private $config;

    public function __construct(\srag\Plugins\Opencast\Model\Cache\Config $config, \ilDBInterface $db)
    {
        $this->config = $config;
        $this->adaptor_factory = new Factory();
    }

    public function get(Request $for_container): Container
    {
        return $this->ensureContainer($for_container);
    }

    private function ensureContainer(Request $for_container): Container
    {
        if (!array_key_exists($for_container->getContainerKey(), $this->containers)) {
            $this->containers[$for_container->getContainerKey()] = $this->new($for_container);
        }
        return $this->containers[$for_container->getContainerKey()];
    }

    private function new(Request $for_container): Container
    {
        if (!$this->config->isActivated()) {
            return new VoidContainer($for_container);
        }

        return new ActiveContainer(
            $for_container,
            $this->getAdaptor(),
            $this->config
        );
    }

    private function getAdaptor(): Adaptor
    {
        if (!$this->adaptor instanceof \srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor) {
            $this->adaptor = $this->adaptor_factory->getWithConfig($this->config);
        }
        return $this->adaptor;
    }

    public function flushContainer(Request $container_request): void
    {
        $this->ensureContainer($container_request)->flush();
    }

    public function flushAdapter(): void
    {
        $this->getAdaptor()->flush();
    }
}
