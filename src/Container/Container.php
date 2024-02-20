<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Container;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 *
 * We use this dependency injection container at the moment as follows:
 * We put dependencies that we need in code into this container whenever possible and get it from there. The convention is that we register the dependency with its FQDN in the container, if possible always with an interface, which simplifies the exchange of the implementation.
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
}
