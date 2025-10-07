<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Series;

use ILIAS\Data\URI;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
interface SeriesActionTargetResolver
{
    public function resolve(SeriesActionTarget $target, ?SeriesActionParameters $parameter = null): ?URI;

    public function supports(SeriesActionTarget $target): bool;

    public function resolveParameter(SeriesActionParameter $parameter): mixed;

}
