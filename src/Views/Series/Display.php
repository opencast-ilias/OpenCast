<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\UI\Component\Component;
use srag\Plugins\Opencast\Views\ViewElement;
use srag\Plugins\Opencast\UI\Integration\Integration;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Display implements ViewElement
{
    public function __construct(
        private Integration $ui_integration,
        private string $series_id
    ) {
    }

    public function get(): Component|array
    {
        try {
            $components = [];

            foreach (
                $this
                    ->ui_integration
                    ->series()
                    ->asEntityListInPanelWithFilter(
                        $this->series_id
                    ) as $item
            ) {
                $components[] = $item;
            }

            return $components;
        } catch (\Throwable $t) {
            return iterator_to_array($this->ui_integration->series()->notFound($this->series_id, $t->getMessage()));
        }
    }

}
