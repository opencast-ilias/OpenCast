<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\UI\Integration\Action;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\UI\Integration\ActionType;
use srag\Plugins\Opencast\UI\MakeURI;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class BaseActionResolver
{
    use MakeURI;

    public function __construct(
        protected Translator $translator,
        protected Services $http,
        protected \ilCtrlInterface $ctrl
    ) {
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
