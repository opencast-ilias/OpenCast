<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\State;

use ilSession;

/**
 * Session-backed {@see ScopedSettingsStore}. This is the only place that touches
 * ilSession, so the backing storage can be replaced without affecting consumers.
 *
 * The ILIAS session is already per-user, which gives us the per-user scoping for
 * free. All values live under a single session key as a nested array
 * (scope => key => value), which ilSession serialises into the DB session.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class SessionSettingsStore implements ScopedSettingsStore
{
    private const SESSION_KEY = 'xoct_ui_settings';

    public function get(string $scope, string $key): ?string
    {
        $value = $this->all()[$scope][$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function set(string $scope, string $key, string $value): void
    {
        $all = $this->all();
        $all[$scope][$key] = $value;
        ilSession::set(self::SESSION_KEY, $all);
    }

    public function remove(string $scope, string $key): void
    {
        $all = $this->all();
        if (!isset($all[$scope][$key])) {
            return;
        }

        unset($all[$scope][$key]);
        ilSession::set(self::SESSION_KEY, $all);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function all(): array
    {
        $all = ilSession::get(self::SESSION_KEY);
        return is_array($all) ? $all : [];
    }
}
