<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util;

use ILIAS\UI\Component\Component;
use ILIAS\Filesystem\Stream\Streams;

trait OutputResponse
{
    protected function out(Component ...$components): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();
        $DIC->ui()->mainTemplate()->setContent($renderer->render($components));
    }

    protected function outAsync(Component ...$components): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();
        $this->sendReponse($renderer->renderAsync($components));
    }

    /**
     * @return never
     */
    protected function sendReponse(string $data): void
    {
        global $DIC;

        $DIC->http()->saveResponse(
            $DIC->http()->response()->withBody(
                Streams::ofString($data)
            )
        );
        $DIC->http()->sendResponse();
        $this->closeResponse();
    }

    /**
     * @return never
     */
    protected function sendJsonResponse(string $data): void
    {
        global $DIC;

        $DIC->http()->saveResponse(
            $DIC->http()->response()->withHeader('Content-Type', 'application/json')
        );

        $this->sendReponse($data);
    }

    /**
     * @return never
     */
    protected function closeResponse(): void
    {
        global $DIC;

        $DIC->http()->close();
    }

}
