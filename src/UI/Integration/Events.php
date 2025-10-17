<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Renderer;
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
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameter;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameters;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTarget;
use ILIAS\UI\Component\Button\Shy;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettings;
use ILIAS\UI\Component\JavaScriptBindable;

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
    private Renderer $ui_renderer;

    public function __construct(
        private UIFactory $ui_factory,
        private Container $container,
        private EventActionTargetResolver $resolver,
        private EventSettingsValueResolver $settings_resolver
    ) {
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $this->ui_renderer = $this->container->ilias()->ui()->renderer();
    }

    public function map(UIFactory $ui_factory, mixed $record): Entity
    {
        $record = $this->event_repository->find($record['identifier'] ?? '');
        $actions = $this->buildActions($record);
        $play_action = $actions[EventActionTarget::PLAY->value] ?? null;

        $lables_as_glyphs = $this->settings_resolver->resolve(EventSettings::LABELS_AS_GLYPHS);

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
                    fn($id): string => "let link = document.getElementById('" . $id . "').parentNode; 
                        link.classList.add('playable');
                        link.setAttribute('target', '_blank');
                        "
                );
            ;
        }

        // Base Entity
        $title = $play_action === null
            ? $record->getTitle()
            : $this->ui_factory->link()->standard(
                $record->getTitle(),
                (string) $play_action
            )->withOpenInNewViewport(true);

        $entity = $this->ui_factory
            ->entity()
            ->standard(
                $title,
                $thumbnail
            );

        // Main Details
        $description = $this->shortenText($record->getDescription());
        $main_properties = [];
        $main_properties_simple = [];

        $translate_date = $this->translate("event_date");
        $event_label = $lables_as_glyphs
            ? $this->ui_renderer->render(
                $this->setAttribute(
                    $this->ui_factory
                        ->symbol()
                        ->glyph()
                        ->time(),
                    'title',
                    $translate_date
                )
            )
            : $translate_date;
        $main_properties[] = $this->ui_factory->listing()->property()->withProperty(
            $event_label,
            $event_label . ' ' . $this->formatDate($record->getStart()),
            false
        );
        $main_properties_simple[$this->translate("event_date")] = $this->formatDate($record->getStart());

        if (!empty($description)) {
            $main_properties[] = $this->ui_factory->listing()->property()->withProperty(
                $this->translate("event_description"),
                $description,
                false
            );
            $main_properties_simple[$this->translate("event_description")] = $description;
        }

        $translate_presenter = $this->translate("event_presenter");
        $presenter_label = $lables_as_glyphs
            ? $this->ui_renderer->render(
                $this->setAttribute(
                    $this->ui_factory
                        ->symbol()
                        ->glyph()
                        ->user(),
                    'title',
                    $translate_presenter
                )
            )
            : $translate_presenter;
        $main_properties[] = $this->ui_factory->listing()->property()->withProperty(
            $presenter_label,
            $presenter_label . ' ' . implode(", ", $record->getPresenter()),
            false
        );
        $main_properties_simple[$this->translate("event_presenter")] = implode(", ", $record->getPresenter());

        if ($this->settings_resolver->resolve(EventSettings::SHOW_OWNER)) {
            $owner_username = $this->container->legacy()->acl_utils()->getOwnerUsernameOfEvent($record);
            $translate_owner = $this->translate("event_owner");
            $owner_label = $lables_as_glyphs
                ? $this->ui_renderer->render(
                    $this->setAttribute(
                        $this->ui_factory
                            ->symbol()
                            ->glyph()
                            ->user(),
                        'title',
                        $translate_owner
                    )
                )
                : $translate_owner;
            $main_properties[] = $this->ui_factory->listing()->property()->withProperty(
                $owner_label,
                $owner_label . ' ' . $owner_username,
                false
            );
            $main_properties_simple[$this->translate("event_owner")] = $owner_username;
        }

        $translate_location = $this->translate("event_location");
        $location_label = $lables_as_glyphs
            ? $this->ui_renderer->render(
                $this->setAttribute(
                    $this->ui_factory
                        ->symbol()
                        ->glyph()
                        ->note(),
                    'title',
                    $translate_location
                )
            )
            : $translate_location;

        $main_properties[] = $this->ui_factory->listing()->property()->withProperty(
            $location_label,
            $location_label . ' ' . ($record->getLocation() ?: '-'),
            false
        );
        $main_properties_simple[$this->translate("event_location")] = $record->getLocation() ?: '-';

        $entity = $entity->withMainDetails(
            //            ...$main_properties
            $this->ui_factory->legacy(
                $this->ui_renderer->render(
                    $this->ui_factory->listing()->descriptive(
                        $main_properties_simple
                    )
                )
            )
        );

        // Status as Tag
        if ($record->getProcessingState() !== Event::STATE_SUCCEEDED) {
            $status_label = $this->translate('event_state_' . strtolower($record->getProcessingState()));
            $status_label_short = $this->shortenText($status_label, 41);

            $this->tooltips[] = $tooltip = $this->ui_factory
                ->popover()
                ->standard(
                    $this->ui_factory
                        ->divider()
                        ->horizontal()
                        ->withLabel($status_label)
                )
                ->withTitle($this->translate('event_processing_state'));

            $entity = $entity->withReactions(
                $this->ui_factory->button()->tag(
                    $status_label_short,
                    '#'
                )->withOnHover($tooltip->getShowSignal())
            );
        }

        // remove 'play' action from list of all actions, as it is already used as main action
        if (isset($actions[EventActionTarget::PLAY->value])) {
            unset($actions[EventActionTarget::PLAY->value]);
        }

        // All Actions
        $entity = $entity->withActions(
            ...array_map(fn(Action $action): Shy => $this->ui_factory->button()->shy(
                $action->name(),
                (string) $action->target()
            ), $actions),
        );

        return $entity;
    }

    private function setAttribute(JavaScriptBindable $c, string $attribute, string $value): JavaScriptBindable
    {
        return $c->withAdditionalOnLoadCode(
            fn(
                $id
            ): string => "document.getElementById('" . $id . "').setAttribute('" . $attribute . "', '" . $value . "');"
        );
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
