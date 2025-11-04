<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\Data\URI;
use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\UI\Integration\Action;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\UI\Integration\ActionType;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class BaseActionResolver
{
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
        $url = parse_url($target_by_class);

        $target = new URI(
            (string) $this->http
                ->request()
                ->getUri()
                ->withQuery($url["query"] ?? "")
                ->withPath($url["path"] ?? "")
        );

        return new Action(
            $translation,
            $target,
            $type ?? ActionType::EXTERNAL_LINK
        );
    }
}
