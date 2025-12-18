<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\UI\Component\Component;
use srag\Plugins\Opencast\Views\ViewElement;
use srag\Plugins\Opencast\UI\Integration\Integration;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use ILIAS\DI\UIServices;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Display implements ViewElement
{
    private string $series_id;

    public function __construct(
        private UIServices $ui,
        private Integration $ui_integration,
        private ObjectSettings $object_settings
    ) {
        $this->series_id = $this->object_settings->getSeriesIdentifier();
    }

    public function get(): Component|array
    {
        try {
            $components = [];

            if ($this->object_settings->getIntroductionText() !== "") {
                $components[] = $this
                    ->ui
                    ->factory()
                    ->messageBox()
                    ->info(
                        $this->object_settings->getIntroductionText()
                    );
            }

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
