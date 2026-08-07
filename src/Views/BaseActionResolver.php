<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\UI\Integration\Action;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\UI\Integration\ActionType;
use srag\Plugins\Opencast\UI\MakeURI;
use srag\Plugins\Opencast\State\ScopedSettingsStore;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class BaseActionResolver
{
    use MakeURI;

    public function __construct(
        protected Translator $translator,
        protected Services $http,
        protected \ilCtrlInterface $ctrl,
        protected ?ScopedSettingsStore $settings_store = null
    ) {
    }

    /**
     * Resolves a value that should be remembered per user under the given scope:
     * an explicit request value is persisted and returned; otherwise the last
     * persisted value is returned (null if there is none and the caller applies
     * its own default).
     *
     * The $scope is supplied by the caller (e.g. the series id of the table), so
     * the resolver does not need to know how the consumer identifies itself.
     * No-op pass-through when no store is configured, so resolvers can opt in
     * simply by being constructed with a store.
     *
     * @param string      $scope group the value belongs to, e.g. the series id of the table
     * @param string      $key   identifies the setting within the scope
     * @param string|null $raw   explicit request value, null when the request carries none
     * @return string|null the persisted value, or null when nothing has been stored yet
     */
    protected function persisted(string $scope, string $key, ?string $raw): ?string
    {
        if ($this->settings_store === null) {
            return $raw;
        }

        if ($raw !== null) {
            $this->settings_store->set($scope, $key, $raw);
            return $raw;
        }

        return $this->settings_store->get($scope, $key);
    }

    protected function build(
        string $translation,
        string|array $target_class,
        ?string $cmd = null,
        ?ActionType $type = null
    ): Action {
        $target_by_class = $this->ctrl->getLinkTargetByClass(
            $target_class,
            $cmd
        );

        return new Action(
            $translation,
            $type ?? ActionType::EXTERNAL_LINK,
            $this->toURI($target_by_class, $this->http)
        );
    }

}
