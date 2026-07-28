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
    private bool $has_scheduled_events = false;

    public function __construct(
        private UIServices $ui,
        private Integration $ui_integration,
        private ObjectSettings $object_settings,
        private bool $debug = true
    ) {
        $this->series_id = $this->object_settings->getSeriesIdentifier();
    }

    public function get(): Component|array
    {
        $series = $this
            ->ui_integration
            ->series();

        try {
            $components = [];

            if ($this->object_settings->getIntroductionText() !== "") {
                $components[] = $this
                    ->ui
                    ->factory()
                    ->messageBox()
                    ->info(
                        // The message box renders its text as raw HTML but the stored work
                        // instruction keeps its line breaks as newlines, which HTML collapses.
                        // Convert them to <br> so the breaks show in the Content tab (see #524).
                        nl2br($this->object_settings->getIntroductionText())
                    );
            }

            foreach (
                $series
                    ->asEntityListInPanelWithFilter(
                        $this->series_id
                    ) as $item
            ) {
                $components[] = $item;
            }

            $this->has_scheduled_events = $series->hasScheduledEvents(
                $this->series_id
            ); // @see hasScheduledEvents() for legacy purposes

            return $components;
        } catch (\Throwable $t) {
            if ($this->debug) {
                throw $t;
            }

            return iterator_to_array($series->notFound($this->series_id, $t->getMessage()));
        }
    }

    /**
     * @deprecated this is only needed for legacy purposes and should be removed in the future
     */
    public function hasScheduledEvents(): bool
    {
        return $this->has_scheduled_events;
    }

}
