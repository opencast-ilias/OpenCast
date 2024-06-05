<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Container;

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\UI\Integration\Integration;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 *
 * We use this dependency injection container at the moment as follows:
 * We put dependencies that we need in code into this container whenever possible and get it from there. The convention is that we register the dependency with its FQDN in the container, if possible always with an interface, which simplifies the exchange of the implementation.
 *
 * @see \srag\Plugins\Opencast\Container\Init::init() for the registration of dependencies
 */
final class Container extends \ILIAS\DI\Container
{
    public function glue(string $fqdn, \Closure $factory): void
    {
        $this[$fqdn] = $this->factory($factory);
    }

    public function get(string $fqdn): object
    {
        return $this[$fqdn];
    }

    public function ilias(): \ILIAS\DI\Container
    {
        return $this->get(\ILIAS\DI\Container::class);
    }

    public function plugin(): \ilOpenCastPlugin
    {
        return $this->get(\ilOpenCastPlugin::class);
    }

    public function translator(): Translator
    {
        return $this->get(Translator::class);
    }

    /**
     * @deprecated We should use the new container instead of the legacy container.
     * but therefore we must move all dependencies to the new container first.
     */
    public function legacy(): OpencastDIC
    {
        return $this->get(OpencastDIC::class);
    }

    public function uiIntegration(\ilPlugin $other_plugin): Integration
    {
        return $this->get(Integration::class);
    }
}
