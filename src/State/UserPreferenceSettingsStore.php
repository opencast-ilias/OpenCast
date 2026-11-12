<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\State;

/**
 * Keeps the values of {@see ScopedSettingsStore::SCOPE_USER} in the preferences
 * of the user, so they apply to all objects and outlive the session (#551).
 * Every other scope, and everything of the anonymous user, who has no
 * preferences of their own, goes to the store this one wraps.
 *
 * All values live under a single preference as a JSON object (key => value).
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class UserPreferenceSettingsStore implements ScopedSettingsStore
{
    private const PREF_KEY = 'xoct_ui_settings';

    public function __construct(
        private ScopedSettingsStore $fallback,
        private \ilObjUser $user
    ) {
    }

    public function get(string $scope, string $key): ?string
    {
        if (!$this->isPersistent($scope)) {
            return $this->fallback->get($scope, $key);
        }

        $value = $this->all()[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function set(string $scope, string $key, string $value): void
    {
        if (!$this->isPersistent($scope)) {
            $this->fallback->set($scope, $key, $value);
            return;
        }

        $all = $this->all();
        if (($all[$key] ?? null) === $value) {
            return;
        }
        $all[$key] = $value;
        $this->user->writePref(self::PREF_KEY, json_encode($all, JSON_THROW_ON_ERROR));
    }

    public function remove(string $scope, string $key): void
    {
        if (!$this->isPersistent($scope)) {
            $this->fallback->remove($scope, $key);
            return;
        }

        $all = $this->all();
        if (!array_key_exists($key, $all)) {
            return;
        }
        unset($all[$key]);
        $this->user->writePref(self::PREF_KEY, json_encode($all, JSON_THROW_ON_ERROR));
    }

    private function isPersistent(string $scope): bool
    {
        return $scope === self::SCOPE_USER
            && $this->user->getId() > 0
            && !$this->user->isAnonymous();
    }

    /**
     * @return array<string, string>
     */
    private function all(): array
    {
        $all = json_decode($this->user->getPref(self::PREF_KEY) ?? '', true);
        return is_array($all) ? $all : [];
    }
}
