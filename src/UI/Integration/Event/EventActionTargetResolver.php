<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

use srag\Plugins\Opencast\UI\Integration\Action;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
interface EventActionTargetResolver
{
    public function resolve(
        EventActionTarget $target,
        ?EventActionParameters $parameter = null,
        ?EventSettingsValueResolver $settings = null
    ): ?Action;

    public function resolveBestForEventStatus(string $status, EventActionParameters $parameter): ?Action;

    public function supports(
        EventActionTarget $target,
        EventActionParameters $parameters,
        ?EventSettingsValueResolver $settings = null
    ): bool;

    public function resolveParameter(EventActionParameter $parameter): mixed;

}
