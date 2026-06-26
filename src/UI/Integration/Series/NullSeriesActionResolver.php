<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Series;

use ILIAS\Data\URI;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class NullSeriesActionResolver implements SeriesActionTargetResolver
{
    public function resolve(SeriesActionTarget $target, ?SeriesActionParameters $parameter = null): ?URI
    {
        return null;
    }

    public function supports(SeriesActionTarget $target): bool
    {
        return true;
    }

    public function resolveParameter(SeriesActionParameter $parameter): mixed
    {
        return null;
    }

    public function resolvePersistentParameter(SeriesActionParameter $parameter, string $scope): mixed
    {
        return null;
    }

}
