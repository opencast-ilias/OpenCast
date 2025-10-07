<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\Data\URI;
use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTarget;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionParameters;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionParameter;
use srag\Plugins\Opencast\Util\Locale\Translator;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class SeriesActionResolver extends BaseActionResolver implements SeriesActionTargetResolver
{
    private URI $current_url;

    public function __construct(
        Translator $translator,
        Services $http,
        \ilCtrlInterface $ctrl
    ) {
        parent::__construct($translator, $http, $ctrl);
        // funny it's xoctEventGUI as well here, but we leave it like this for now
        $this->current_url = $this->build('lorem', \xoctEventGUI::class)->target();
    }

    public function resolve(SeriesActionTarget $target, ?SeriesActionParameters $parameter = null): ?URI
    {
        return match ($target) {
            SeriesActionTarget::SORT, SeriesActionTarget::FILTER, SeriesActionTarget::PAGE => $this->current_url,
            default => null,
        };
    }

    public function supports(SeriesActionTarget $target): bool
    {
        return match ($target) {
            SeriesActionTarget::SORT,
            SeriesActionTarget::FILTER,
            SeriesActionTarget::PAGE => true,
            default => false,
        };
    }

    public function resolveParameter(SeriesActionParameter $parameter): mixed
    {
        return match ($parameter) {
            SeriesActionParameter::PAGE => (int) ($this->http->request()->getQueryParams()[$parameter->value] ?? 0),
            default => $this->http->request()->getQueryParams()[$parameter->value] ?? null,
        };
    }

}
