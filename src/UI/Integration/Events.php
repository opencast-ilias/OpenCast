<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Event\Event;
use ILIAS\UI\Component\Item\Item;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use ILIAS\UI\Component\Panel\Panel;
use ILIAS\UI\Component\Button\Standard;
use ILIAS\UI\Component\Listing\Entity\RecordToEntity;
use ILIAS\UI\Component\Entity\Entity;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Button\Tag;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameter;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameters;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTarget;
use ILIAS\UI\Component\Button\Shy;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Events implements RecordToEntity
{
    use Commons;

    private EventAPIRepository $event_repository;
    private SeriesAPIRepository $series_repository;

    private array $tooltips = [];

    public function __construct(
        private UIFactory $ui_factory,
        private Container $container,
        private EventActionTargetResolver $resolver
    ) {
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
    }

    public function map(UIFactory $ui_factory, mixed $record): Entity
    {
        $record = $this->event_repository->find($record['identifier'] ?? '');

        $actions = $this->buildActions($record);
        $play_action = $actions[EventActionTarget::PLAY->value] ?? null;
        $download_action = $actions[EventActionTarget::DOWNLOAD->value] ?? null;

        // Thumbnail
        $thumbnail = $this->ui_factory
            ->image()
            ->responsive(
                $record->publications()->getThumbnailUrl(),
                'Preview of Video: ' . $record->getTitle()
            );

        if ($play_action) {
            $thumbnail = $thumbnail
                ->withAction((string) $play_action)
                ->withAdditionalOnLoadCode(
                    fn($id): string => "document.getElementById('" . $id . "').parentNode.classList.add('playable');"
                );
            ;
        }

        // Base Entity
        $entity = $this->ui_factory
            ->entity()
            ->standard(
                $record->getTitle(),
                $thumbnail
            );

        // Main Details
        $entity = $entity->withMainDetails(
            $this->ui_factory->listing()->property()->withProperty(
                'Description',
                $this->shortenText($record->getDescription()),
                false
            ),
            $this->ui_factory->listing()->property()->withProperty(
                'Speaker',
                implode(", ", $record->getPresenter())
            ),
            $this->ui_factory->listing()->property()->withProperty('Raum', $record->getLocation()),
        );

        // Owner Tooltip in Prioritized Reactions
        $this->tooltips[] = $tooltip = $this->ui_factory
            ->popover()
            ->standard(
                $this->ui_factory->legacy(implode(", ", $record->getPresenter()))
            )
            ->withTitle('Speaker');

        $entity = $entity->withPrioritizedReactions(
            $this->ui_factory->symbol()->glyph()->user()->withOnClick($tooltip->getShowSignal())
        );

        // Actions as Reactions
        $reactions = [];
        if ($play_action) {
            $reactions[] = $this->ui_factory->button()->tag(
                $play_action->name(),
                (string) $play_action
            )->withRelevance(Tag::REL_MID);
        }
        if ($download_action) {
            $reactions[] = $this->ui_factory->button()->tag(
                $download_action->name(),
                (string) $download_action
            )->withRelevance(Tag::REL_MID);
        }
        if (!empty($reactions)) {
            $entity = $entity->withReactions(...$reactions);
        }

        // All Actions
        $entity = $entity->withActions(
            ...array_map(fn(Action $action): Shy => $this->ui_factory->button()->shy(
                $action->name(),
                (string) $action->target()
            ), $actions),
        );
        // Featured Properties
        $entity = $entity->withFeaturedProperties(
            $this->ui_factory->listing()->property()->withProperty(
                'Date',
                $record->getStart()->format('d.m.Y H:i'),
                false
            ),
        );

        return $entity;
    }

    private function shortenText(string $text, int $max_length = 100): string
    {
        if (strlen($text) <= $max_length) {
            return $text;
        }

        return substr($text, 0, $max_length - 3) . '...';
    }

    public function asItemFromEventId(
        string $event_id,
        ?Standard $main_action = null,
        ?array $additional_actions = null,
        ?string $surround_with_panel = null
    ): Item|Panel {
        $event = $this->event_repository->find($event_id);
        $item = $this->asItem($event, $main_action, $additional_actions);

        if ($surround_with_panel === null) {
            return $item;
        }

        return $this->ui_factory->panel()->standard($surround_with_panel, $item);
    }

    public function asItem(
        Event $event,
        ?Standard $main_action = null,
        ?array $additional_actions = null
    ): Item {
        $item = $this->ui_factory
            ->item()
            ->standard(
                $event->getTitle(),
            );

        $lead_image = $this->ui_factory->image()->responsive(
            $event->publications()->getThumbnailUrl(),
            'src'
        );

        if ($main_action !== null) {
            $item = $item->withMainAction($main_action);
            $lead_image = $lead_image->withAction($main_action->getAction());
        }

        if (!empty($additional_actions)) {
            $item = $item->withActions(
                $this->ui_factory->dropdown()->standard($additional_actions)
            );
        }

        return $item->withProperties([
            $this->translate("event_date") => $event->getStart()->format('d.m.Y H:i'),
            $this->translate("event_series") => $this->getSeriesName($event),
            $this->translate("event_presenter") => implode(", ", $event->getPresenter()),
        ])->withLeadImage(
            $lead_image
        );
    }

    public function getTooltips(): array
    {
        return $this->tooltips;
    }

    private function buildActions(Event $event): array
    {
        // Pass Parameters to Resolver
        $parameters = (new EventActionParameters())
            ->with(EventActionParameter::EVENT_ID, $event->getIdentifier())
            ->with(EventActionParameter::EVENT_OBJECT, $event);

        // Build Actions for Event
        $actions = [];
        foreach (EventActionTarget::cases() as $case) {
            if (!$this->resolver->supports($case, $parameters)) {
                continue;
            }
            $actions[$case->value] = $this->resolver->resolve($case, $parameters);
        }

        // Filter out null actions
        $actions = array_filter($actions, fn(?Action $action): bool => $action !== null);

        return $actions;
    }

}
