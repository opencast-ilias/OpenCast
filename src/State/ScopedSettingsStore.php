<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\State;

/**
 * Persists small, per-user UI state (e.g. a chosen sort order or page size)
 * under an arbitrary scope.
 *
 * The store is implicitly scoped to the current user: the implementation decides
 * how that scoping is realised (the ILIAS session is already per-user; a future
 * database-backed implementation would include the user id). Keeping the user
 * out of this interface lets us swap the backing storage without touching any
 * consumer.
 *
 * The $scope groups values that belong together (e.g. one Opencast object /
 * table); $key identifies the individual setting within that scope.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface ScopedSettingsStore
{
    /**
     * Scope for values that belong to the user rather than to one object, such as
     * the sorting of the event lists (#551). Implementations may keep them beyond
     * the session.
     */
    public const SCOPE_USER = 'user';

    public function get(string $scope, string $key): ?string;

    public function set(string $scope, string $key, string $value): void;

    public function remove(string $scope, string $key): void;
}
