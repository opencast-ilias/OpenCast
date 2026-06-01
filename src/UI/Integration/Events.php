<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
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
use ILIAS\UI\Component\Input\Field\Select;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Data\URI;
use srag\Plugins\Opencast\UI\Integration\Event\Publications;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;

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
    private array $modals = [];
    private Renderer $ui_renderer;
    private Publications $publications;

    public function __construct(
        private UIFactory $ui_factory,
        private Container $container,
        private EventActionTargetResolver $resolver,
        private EventSettingsValueResolver $settings_resolver
    ) {
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $this->ui_renderer = $this->container->ilias()->ui()->renderer();
        $this->publications = new Publications(
            $this->ui_factory,
            $this->container,
            $this->resolver,
            $this->settings_resolver
        );
    }

    public function publications(): Publications
    {
        return $this->publications;
    }

    public function asPublicationSelection(string $event_id): Select
    {
        $event = $this->event_repository->find($event_id);

        $publication = [];

        foreach ($event->publications()->getDownloadPublications() as $pub) {
            $publication[$pub->getId()] = $pub->getId();
        }

        return $this
            ->ui_factory
            ->input()
            ->field()
            ->select(
                $this->translate('publication_usage_md_type_0'),
                $publication
            )
            ->withRequired(true);
    }

    public function asPublicationSelectionInModal(
        string $event_id,
        URI|string $submit_target
    ): RoundTrip {
        return $this
            ->ui_factory
            ->modal()
            ->roundtrip(
                $this->translate('publication_usage_md_type_0'),
                null,
                [$this->asPublicationSelection($event_id)],
                (string) $submit_target
            )
            ->withSubmitLabel(
                $this->translate('event_download')
            );
    }

    public function map(UIFactory $ui_factory, mixed $record): Entity
    {
        $record = $this->event_repository->find($record['identifier'] ?? '');
        // Pass Parameters to Resolver
        $parameters = (new EventActionParameters())
            ->with(EventActionParameter::EVENT_ID, $record->getIdentifier())
            ->with(EventActionParameter::EVENT_OBJECT, $record);

        $actions = $this->buildActions($parameters);
        $play_action = $actions[EventActionTarget::PLAY->value] ?? null;

        // Thumbnail
        $thumbnail = $this->ui_factory
            ->image()
            ->responsive(
                $record->publications()->getThumbnailUrl(),
                'Preview of Video: ' . $record->getTitle()
            );

        $title = $record->getTitle();

        if ($play_action) {
            $url = (string) $play_action->target();
            if ($play_action->type() === ActionType::ASYNC_MODAL) {
                $this->modals[] = $play_modal = $this->ui_factory
                    ->modal()
                    ->roundtrip(
                        $play_action->name(),
                        null,
                    )->withAsyncRenderUrl(
                        $url
                    );
                $title = $this
                    ->ui_factory
                    ->button()
                    ->shy($title, '#')
                    ->withOnClick($play_modal->getShowSignal());

                $thumbnail = $thumbnail->withAction($play_modal->getShowSignal());
            } else {
                $title = $this
                    ->ui_factory
                    ->link()
                    ->standard(
                        $title,
                        $url
                    )->withOpenInNewViewport(true);

                $thumbnail = $thumbnail->withAction($url);
            }

            $thumbnail = $thumbnail
                ->withAdditionalOnLoadCode(
                    fn($id): string => "let link = document.getElementById('" . $id . "').parentNode.parentNode.querySelector('a');
                        link.classList.add('playable');
                        link.setAttribute('target', '_blank');
                        "
                );
        }

        // Base Entity
        $entity = $this->ui_factory
            ->entity()
            ->standard(
                $title,
                $thumbnail
            );

        // Metadata
        $shown = $this->settings_resolver->resolve(EventSettings::PRESENTED_METADATA) ?? [
            EventSettingsValueResolver::MD_OWNER,
            EventSettingsValueResolver::MD_LOCATION,
            EventSettingsValueResolver::MD_DESCRITION,
            EventSettingsValueResolver::MD_PRESENTER
        ];

        $description = trim($record->getDescription());
        if (in_array(EventSettingsValueResolver::MD_DESCRITION, $shown, true) && !empty($description)) {
            $shortened_description = $this->shortenText(
                $description,
                (int) ($this->settings_resolver->resolve(EventSettings::DESCRIPTION_MAX_LENGTH) ?? 50)
            );

            $description_components = [];

            $description_components[] = $description_wrapper = $this
                ->ui_factory
                ->legacy(
                    "<span data-full='$description'  id='evdesc_{$record->getIdentifier()}'>$shortened_description</span>"
                )
                ->withCustomSignal(
                    "evdesc_{$record->getIdentifier()}",
                    "document.getElementById('evdesc_{$record->getIdentifier()}').innerHTML = document.getElementById('evdesc_{$record->getIdentifier()}').getAttribute('data-full');"
                );

            if ($shortened_description !== $description) {
                $description_components[] = $this
                    ->ui_factory->button()->shy($this->translate('show_more'), '#')
                                ->withOnClick(
                                    $description_wrapper->getCustomSignal("evdesc_{$record->getIdentifier()}")
                                )->withAdditionalOnLoadCode(
                                    fn(
                                        $id
                                    ): string => "document.getElementById('$id').addEventListener('click', function(event) {let target = event.target || event.srcElement; target.style.display = 'none';});"
                                );
            }

            $entity = $entity->withPersonalStatus(
                $this->ui_factory->legacy(
                    $this->ui_renderer->render($description_components)
                )
            );
        }

        $main_properties_simple = [];

        $main_properties_simple[$this->translate("event_date")] = $this->formatDate($record->getStart());

        if (in_array(EventSettingsValueResolver::MD_PRESENTER, $shown, true)) {
            $main_properties_simple[$this->translate("event_presenter")] = implode(", ", $record->getPresenter());
        }

        if (in_array(EventSettingsValueResolver::MD_OWNER, $shown, true)
            && ($show_owner = $this->settings_resolver->resolve(EventSettings::SHOW_OWNER))
        ) {
            $owner_username = $this->container->legacy()->acl_utils()->getOwnerUsernameOfEvent($record);
            // This should be refactored since it leads to many queries in case of listing many events.
            // but this is how it's currently implemented.
            $in = PermissionGrant::getActiveInvitationsForEvent(
                $record,
                $show_owner,
                true
            );
            // We decided to implement this on our own. The alternative would be a user-glyph with a counter badge.
            $glyph = $in > 0 ? " <span class='xoct-invitations-counter'>+" . $in . "</span>" : '';
            $main_properties_simple[$this->translate("event_owner")] = $owner_username . $glyph;
        }

        if (in_array(EventSettingsValueResolver::MD_LOCATION, $shown, true)) {
            $main_properties_simple[$this->translate("event_location")] = $record->getLocation() ?: '-';
        }

        $entity = $entity->withMainDetails(
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
            $entity = $this->buildStatusComponents($record, $parameters, $entity);
        }

        // remove 'play' action from list of all actions, as it is already used as main action
        if (isset($actions[EventActionTarget::PLAY->value])) {
            unset($actions[EventActionTarget::PLAY->value]);
        }

        // All Actions
        $action_to_buttons = function (Action $action): Shy {
            switch ($action->type()) {
                case ActionType::INTERNAL_LINK:
                    $button = $this
                        ->ui_factory
                        ->button()
                        ->shy(
                            $action->name(),
                            (string) $action->target()
                        );
                    break;
                case ActionType::EXTERNAL_LINK:
                    $button = $this
                        ->ui_factory
                        ->button()
                        ->shy(
                            $action->name(),
                            '#'
                        )
                        ->withAdditionalOnLoadCode(
                            // This is really not nice, but ILIAS does not provide a better way to open links in new tabs from buttons
                            fn($id): string => "document.getElementById('"
                                . $id . "').addEventListener('click', function() { window.open('"
                                . $action->target()
                                . "', '_blank'); });"
                        );
                    break;
                case ActionType::ASYNC_MODAL:
                    $button = $this
                        ->ui_factory
                        ->button()
                        ->shy(
                            $action->name(),
                            '#'
                        );

                    $this->modals[] = $modal = $this->ui_factory
                        ->modal()
                        ->roundtrip(
                            $action->name(),
                            null
                        )->withAsyncRenderUrl(
                            (string) $action->target()
                        );

                    $button = $button->withOnClick($modal->getShowSignal());
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown Action Type: " . $action->type()->value);
            }

            return $button;
        };

        return $entity->withActions(
            ...array_map(
                $action_to_buttons,
                $actions
            )
        );
    }

    protected function buildStatusComponents(Event $record, EventActionParameters $parameters, Entity $entity): Entity
    {
        $status_label = $this->translate('event_state_' . strtolower($record->getProcessingState()));
        // The "scheduled live stream" label contains a %s placeholder for the time from which the
        // stream can be opened. Fill it (the legacy renderer does this; the new UI did not, so the
        // raw "%s" was shown). See #498.
        if (
            $record->getProcessingState() === Event::STATE_LIVE_SCHEDULED
            && str_contains($status_label, '%s')
            && $record->getScheduling() !== null
        ) {
            $minutes_before_live = (int) PluginConfig::getConfig(PluginConfig::F_START_X_MINUTES_BEFORE_LIVE);
            $open_from = $record->getScheduling()->getStart()
                ->modify("-{$minutes_before_live} minutes")
                ->setTimezone($this->userTimeZone());
            $status_label = sprintf($status_label, $open_from->format('d.m.Y, H:i'));
        }
        $status_label_short = $this->shortenText(
            $status_label,
            (int) ($this->settings_resolver->resolve(EventSettings::STATUS_MAX_LENGTH) ?? 42)
        );

        $status_tag = $this->ui_factory->button()->tag(
            $status_label_short,
            '#'
        );

        // We may show best action as tooltip
        if (
            $this->settings_resolver->resolve(EventSettings::SHOW_BEST_ACTION)
            && ($best_action = $this->resolver->resolveBestForEventStatus(
                $record->getProcessingState(),
                $parameters
            )) instanceof Action
        ) {
            $tooltip_content = $this->ui_factory->legacy(
                $this->translate('event_possible_best_action') . ': '
                . $this->ui_renderer->render(
                    $this->ui_factory->button()->shy(
                        $best_action->name(),
                        (string) $best_action->target()
                    )
                )
            );

            $this->tooltips[] = $tooltip = $this->ui_factory
                ->popover()
                ->standard($tooltip_content)
                ->withVerticalPosition();

            $status_tag = $status_tag->withOnClick(
                $tooltip->getShowSignal()
            );
        }

        return $entity->withReactions(
            $status_tag
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
            $this->translate("event_date") => $event->getStart()->setTimezone($this->userTimeZone())->format('d.m.Y H:i'),
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

    public function getModals(): array
    {
        return $this->modals;
    }

    /**
     * @return array<string, Action>
     */
    private function buildActions(EventActionParameters $parameters): array
    {
        // Build Actions for Event
        $actions = [];
        foreach (EventActionTarget::cases() as $case) {
            if (!$this->resolver->supports($case, $parameters, $this->settings_resolver)) {
                continue;
            }
            $actions[$case->value] = $this->resolver->resolve($case, $parameters, $this->settings_resolver);
        }

        // Filter out null actions
        $actions = array_filter($actions, fn(?Action $action): bool => $action !== null);

        return $actions;
    }

}
