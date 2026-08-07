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

    /**
     * Like {@see resolveParameter()}, but remembers the value per user under the
     * given scope (e.g. the series id): an explicit request value is stored and
     * restored on later requests.
     *
     * @param SeriesActionParameter $parameter the parameter to resolve
     * @param string                $scope     groups the stored values, e.g. the series id of the table
     * @return mixed the resolved value, null when neither the request nor the store holds one
     */
    public function resolvePersistentParameter(SeriesActionParameter $parameter, string $scope): mixed;

}
