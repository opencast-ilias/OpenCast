<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use ILIAS\UI\Component\Listing\Entity\DataRetrieval;
use ILIAS\UI\Component\Listing\Entity\Mapping;
use ILIAS\Data\Range;
use ILIAS\DI\UIServices;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionParameter;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTarget;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use ILIAS\UI\Implementation\Component\Input\Field\Text;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettings;
use srag\Plugins\Opencast\Views\Series\Display;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Series implements DataRetrieval
{
    use Commons;
    public $has_scheduled_events;

    public const DEFAULT_PAGE_SIZE = 10;
    public const DEFAULT_SORT = self::SORT_DATE_DESC;
    private const SORT_TITLE_ASC = 'title:asc';
    private const SORT_DATE_ASC = 'date:asc';
    private const SORT_TITLE_DESC = 'title:desc';
    private const SORT_DATE_DESC = 'date:desc';
    private const SORT_PRESENTER_ASC = 'presenter:asc';
    private const SORT_PRESENTER_DESC = 'presenter:desc';
    private const SORT_OWNER_ASC = 'owner:asc';
    private const SORT_OWNER_DESC = 'owner:desc';
    private EventAPIRepository $event_repository;
    private SeriesAPIRepository $series_repository;
    private ?\srag\Plugins\Opencast\Model\Series\Series $series = null;
    private Factory $ui_factory;
    private UIServices $ui;
    private int $total = 0;
    private Translator $translator;
    private \ilUIFilterService $filter_service;
    private MDCatalogue $md_catalogue;
    private MDFieldConfigEventRepository $md_repository;
    private ?Standard $filter = null;

    public function __construct(
        private Container $container,
        private Events $events,
        private SeriesActionTargetResolver $resolver,
        private EventSettingsValueResolver $settings_resolver
    ) {
        $this->ui = $this->container->ilias()->ui();
        $this->filter_service = $this->container->ilias()->uiService()->filter();
        $this->ui_factory = $this->container->ilias()->ui()->factory();
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->translator = $this->container->translator();

        // TODO maybe inject, needed for filters
        $this->md_catalogue = $this->container
            ->legacy()
            ->metadata()
            ->catalogueFactory()
            ->event();

        $this->md_repository = $this->container
            ->legacy()
            ->metadata()
            ->confRepositoryEvent();
    }

    public function notFound(string $series_id, ?string $error = null): \Generator
    {
        $message_box = $this->ui_factory->messageBox()->failure(
            $this->translator->translate(
                'series_not_found'
            )
        );

        if (!is_null($error)) {
            $modal = $this->ui_factory->modal()->lightbox(
                $this->ui_factory->modal()->lightboxTextPage(
                    $error,
                    $this->translator->translate('native_error')
                )
            );
            $show = $this->ui_factory->button()->standard(
                $this->translator->translate('show_native_error'),
                '#',
            )->withOnClick($modal->getShowSignal());

            yield $modal;
            $message_box = $message_box->withButtons([$show]);
        }

        yield $message_box;
    }

    protected function buildSeries(string $series_id): void
    {
        $this->series ??= $this->series_repository->find($series_id);
        if ($this->series === null) {
            throw new \InvalidArgumentException("Series with ID $series_id not found.");
        }
    }

    public function asEntityListInPanelWithFilter(
        string $series_id,
        string $title = '',
    ): \Generator {
        $this->buildSeries($series_id);

        // Filter
        $md_field_configs = $this->md_repository->getAllFilterable(
            \ilObjOpenCastAccess::hasPermission(\ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );

        yield $this->filter = $this->filter_service->standard(
            self::class . $series_id,
            (string) $this->resolver->resolve(SeriesActionTarget::FILTER),
            array_column(
                array_map(
                    fn(MDFieldConfigEventAR $md_field_config): array => [
                        $md_field_config->getFieldId(),
                        $this->buildFilterItem($md_field_config)
                    ],
                    $md_field_configs
                ),
                1,
                0
            ),
            array_map(fn(MDFieldConfigEventAR $md_field_config): bool => true, $md_field_configs),
            false,
            false
        );

        yield from $this->asEntityListInPanel($series_id, $title);
        yield from $this->events->getTooltips();
        yield from $this->events->getModals();
    }

    private function buildFilterItem(MDFieldConfigEventAR $md_field_config): Text
    {
        $factory = $this->ui_factory->input()->field();
        $field_definition = $this->md_catalogue->getFieldById($md_field_config->getFieldId());
        $lang_key = $this->container->ilias()->language()->getLangKey();
        return match ($field_definition->getType()->getTitle()) {
            MDDataType::text()->getTitle(),
            MDDataType::text_array()->getTitle(),
            MDDataType::text_long()->getTitle() => $factory->text($md_field_config->getTitle($lang_key)),
            default => $factory->text($md_field_config->getTitle($lang_key)),
        };
    }

    public function asEntityList(
        string $series_id
    ): \Generator {
        $this->buildSeries($series_id);
        $this->ui->mainTemplate()->addCss(
            "./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events10.css"
        );

        yield $this->ui_factory->listing()->entity()->standard($this->events)->withData($this);
        yield from $this->events->getTooltips();
        yield from $this->events->getModals();
    }

    public function asEntityListInPanel(
        string $series_id,
        string $title = '',
    ): \Generator {
        $this->buildSeries($series_id);

        $entity_list = iterator_to_array($this->asEntityList($series_id));

        $sortation_options = [
            self::SORT_TITLE_ASC => $this->translator->translate('title_asc'),
            self::SORT_TITLE_DESC => $this->translator->translate('title_desc'),
            self::SORT_DATE_ASC => $this->translator->translate('date_asc'),
            self::SORT_DATE_DESC => $this->translator->translate('date_desc'),
            self::SORT_PRESENTER_ASC => $this->translator->translate('presenter_asc'),
            self::SORT_PRESENTER_DESC => $this->translator->translate('presenter_desc'),
        ];

        if ($this->settings_resolver->resolve(EventSettings::SHOW_OWNER)) {
            $sortation_options[self::SORT_OWNER_ASC] = $this->translator->translate('owner_asc');
            $sortation_options[self::SORT_OWNER_DESC] = $this->translator->translate('owner_desc');
        }

        yield $this
            ->ui_factory
            ->panel()
            ->secondary()
            ->legacy(
                $title,
                $this->ui_factory->legacy(
                    $this->ui->renderer()->render(
                        $this->ui_factory->legacy(
                            $this->ui->renderer()->render(
                                $entity_list[0],
                            )
                        )
                    )
                )
            )
            ->withViewControls([
                $this->ui_factory
                    ->viewControl()
                    ->pagination()
                    ->withCurrentPage($this->resolver->resolveParameter(SeriesActionParameter::PAGE))
                    ->withPageSize(
                        $this->resolver->resolveParameter(SeriesActionParameter::PAGE_SIZE) ?? self::DEFAULT_PAGE_SIZE
                    )
                    ->withMaxPaginationButtons(5)
                    ->withTotalEntries($this->total)
                    ->withTargetURL(
                        (string) $this->resolver->resolve(SeriesActionTarget::PAGE),
                        SeriesActionTarget::PAGE->value
                    ),

                $this->ui_factory
                    ->viewControl()
                    ->sortation(
                        [
                            '10' => '10',
                            '20' => '20',
                            '50' => '50',
                        ],
                        (string) ($this->resolver->resolveParameter(
                            SeriesActionParameter::PAGE_SIZE
                        ) ?? self::DEFAULT_PAGE_SIZE)
                    )
                    ->withLabelPrefix('')
                    ->withTargetURL(
                        (string) $this->resolver->resolve(SeriesActionTarget::SET_ITEMS_PER_PAGE),
                        SeriesActionParameter::PAGE_SIZE->value
                    ),

                $this->ui_factory
                    ->viewControl()
                    ->sortation(
                        $sortation_options,
                        $this->resolver->resolveParameter(SeriesActionParameter::SORT) ?? self::DEFAULT_SORT
                    )
                    ->withTargetURL(
                        (string) $this->resolver->resolve(SeriesActionTarget::SORT),
                        SeriesActionTarget::SORT->value
                    )

            ]);
    }

    public function getEntities(Mapping $mapping, ?Range $range, ?array $additional_parameters): \Generator
    {
        $page = $this->resolver->resolveParameter(SeriesActionParameter::PAGE);
        $page_size = (int) ($this->resolver->resolveParameter(SeriesActionParameter::PAGE_SIZE) ?? self::DEFAULT_PAGE_SIZE);
        $sort = $this->resolver->resolveParameter(SeriesActionParameter::SORT) ?? self::DEFAULT_SORT;

        $api_sort = match ($sort) {
            self::SORT_OWNER_ASC, self::SORT_OWNER_DESC => '', // we cannot sort by owner via API
            self::SORT_TITLE_ASC, self::SORT_TITLE_DESC, => $sort,
            default => $sort . ',' . self::SORT_TITLE_ASC // we append title as secondary sort to have a deterministic order
        };

        // Filtered by API
        $filtered = $this->event_repository->getFiltered(
            ['series' => $this->series->getIdentifier()],
            '',
            [],
            $page * $page_size,
            $page_size,
            $api_sort,
        );

        // local sorting for owner
        if (in_array($sort, [self::SORT_OWNER_ASC, self::SORT_OWNER_DESC], true)) {
            usort($filtered, function (array $a, array $b) use ($sort): int {
                /** @var Event $a_object */
                $a_object = $a['object'];
                /** @var Event $b_object */
                $b_object = $b['object'];

                $a_owner = $this->container->legacy()->acl_utils()->getOwnerUsernameOfEvent($a_object);
                $b_owner = $this->container->legacy()->acl_utils()->getOwnerUsernameOfEvent($b_object);
                return match ($sort) {
                    self::SORT_OWNER_ASC => strcasecmp($a_owner, $b_owner),
                    self::SORT_OWNER_DESC => strcasecmp($b_owner, $a_owner),
                };
            });
        }

        // Filtered by UI Filter
        $filter_data = $this->filter === null ? null : $this->filter_service->getData(
            $this->filter
        );

        $ui_filter = function (array $event) use ($filter_data): bool {
            $event_object = $event['object'];
            foreach ($filter_data ?? [] as $key => $value) {
                /** @var Event $event_object */
                $md_value = $event_object->getMetadata()->getField($key)->toString();
                if (stripos($md_value, strtolower($value)) === false) {
                    return false;
                }
            }

            return \ilObjOpenCastAccess::hasReadAccessOnEvent(
                $event_object,
                xoctUser::getInstance($this->container->ilias()->user()),
                $this->container->objectSettings()
            );
        };
        $filtered = array_filter($filtered, $ui_filter);

        // Calculate total count

        $filtered_all = $this->event_repository->getFiltered(
            ['series' => $this->series->getIdentifier()],
            '',
            []
        );
        $filtered_all = array_filter($filtered_all, $ui_filter);

        // @see hasScheduledEvents
        array_walk($filtered_all, function (array $event): void {
            $event_object = $event['object'] ?? null;
            if ($event_object instanceof Event && $event_object->isScheduled()) {
                $this->has_scheduled_events[$this->series->getIdentifier()] = true;
            }
        });

        $this->total = count(
            $filtered_all
        );

        foreach ($filtered as $event) {
            yield $mapping->map($event);
        }
    }

    /**
     * @deprecated
     * @see Display::hasScheduledEvents()
     */
    public function hasScheduledEvents(string $series_id): bool
    {
        return $this->has_scheduled_events[$series_id] ?? false;
    }


}
