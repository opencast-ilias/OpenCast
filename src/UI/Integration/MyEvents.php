<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Component\Table\Data;
use srag\Plugins\Opencast\Container\Container;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\Refinery\Transformation;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use ILIAS\UI\Implementation\Component\Input\Input;
use ILIAS\UI\Component\Item\Group;
use srag\Plugins\Opencast\Model\Event\Event;
use ILIAS\Data\URI;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\Data\Order;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use Generator;
use ILIAS\Data\Factory;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Icon;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class MyEvents implements DataRetrieval
{
    use Commons;

    /**
     * @readonly
     */
    private \ILIAS\Refinery\Factory $refinery;
    /**
     * @readonly
     */
    private EventAPIRepository $event_repository;
    /**
     * @readonly
     */
    private SeriesAPIRepository $series_repository;
    private ?array $event_cache = null;
    private ?URI $target_url = null;
    private string $parameter_name = 'event_id';
    /**
     * @readonly
     */
    private \ilUIFilterService $filter_service;
    private ?array $filter_data = null;
    /**
     * @readonly
     */
    private xoctUser $user;
    private \ILIAS\UI\Renderer $ui_renderer;

    public function __construct(
        private \ILIAS\UI\Factory $ui_factory,
        private Container $container,
        private Events $events
    ) {
        $this->refinery = $this->container->ilias()->refinery();
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $this->filter_service = $this->container->ilias()->uiService()->filter();
        $this->user = $this->container->get(xoctUser::class);
        $this->ui_renderer = $this->container->ilias()->ui()->renderer();
    }

    protected function getFilter(URI $target_url): \ILIAS\UI\Component\Input\Container\Filter\Standard
    {
        // init series
        $series = [];
        foreach ($this->series_repository->getAllForUser($this->user->getUserRoleName()) as $item) {
            $series[$item->getIdentifier()] = $item->getMetadata()->getField('title')->getValue();
        }
        uasort($series, 'strnatcasecmp');

        $inputs = [
            'textFilter' => $this->ui_factory->input()->field()->text(
                $this->container->translator()->translate('event_title')
            ),
            'series' => $this->ui_factory->input()->field()->select(
                $this->container->translator()->translate('event_series'),
                $series
            )
            //            ,'start' => $this->ui_factory->input()->field()->text(
            //                $this->container->translator()->translate('event_start')
            //            )->withValue((new \DateTime())->format('d.m.Y')),
        ];
        $filter = $this->filter_service->standard(
            self::class,
            (string) $target_url,
            $inputs,
            array_map(static fn($key): string => $key, array_keys($inputs)),
            true,
            true
        );

        $this->filter_data = $this->filter_service->getData($filter);
        return $filter;
    }

    public function asItemGroupWithFilters(
        URI $calling_url,
        URI $target_url,
        string $parameter_name = 'event_id'
    ): array {
        $filter = $this->getFilter($calling_url);

        return [
            $filter,
            $this->asItemGroup($target_url, $parameter_name)
        ];
    }

    public function asItemGroup(
        URI $target_url,
        string $parameter_name = 'event_id'
    ): Group {
        $items = [];

        /** @var Event $event */
        foreach ($this->getEvents() as $event) {
            $action = (string) $target_url->withParameter(
                $parameter_name,
                $event->getIdentifier()
            );
            $actions = [
                $this->ui_factory->link()->standard(
                    $this->translate("select"),
                    $action
                ),
            ];
            $items[] = $this->events->asItem($event, $action, $actions);
        }

        return $this->ui_factory->item()->group(
            $this->translate("config_events"),
            $items
        );
    }

    public function asForm(URI $post_url, ?Transformation $transformation = null): Standard
    {
        return $this->ui_factory->input()->container()->form()->standard(
            (string) $post_url,
            [
                $this->asFormSection($transformation)
            ]
        );
    }

    public function asFormSection(?Transformation $transformation = null): Section
    {
        return $this->ui_factory->input()->field()->section(
            [
                $this->asInput($transformation)
            ],
            $this->container->translator()->translate("config_events"),
        );
    }

    public function asDataTableWithFilters(
        URI $calling_url,
        URI $target_url,
        string $parameter_name = 'event_id'
    ): array {
        $filter = $this->getFilter($calling_url);

        return [
            $filter,
            $this->asDataTable($target_url, $parameter_name)
        ];
    }

    public function asDataTable(
        URI $target_url,
        string $parameter_name = 'event_id'
    ): Data {
        $this->target_url = $target_url;
        $this->parameter_name = $parameter_name;

        $factory = new Factory();
        $date_format = $factory->dateFormat()->withTime24($factory->dateFormat()->standard());

        return $this->ui_factory->table()->data(
            $this->container->translator()->translate("config_events"),
            [
                'preview' => $this->ui_factory->table()->column()->statusIcon(
                    $this->container->translator()->translate("event_preview")
                )->withIsSortable(false),
                'title' => $this->ui_factory->table()->column()->text(
                    $this->container->translator()->translate("event_title")
                ),
                'date' => $this->ui_factory->table()->column()->date(
                    $this->container->translator()->translate("event_date"),
                    $date_format
                ),
                'series' => $this->ui_factory->table()->column()->text(
                    $this->container->translator()->translate("event_series")
                ),
                'presenter' => $this->ui_factory->table()->column()->text(
                    $this->container->translator()->translate("event_presenter")
                )->withIsOptional(false), // could be optional in the future
                /*'status' => $this->ui_factory->table()->column()->text(
                    $this->container->translator()->translate("event_processing_state")
                )->withIsOptional(true),*/
                'action' => $this->ui_factory->table()->column()->text(
                    $this->container->translator()->translate("select")
                )->withIsSortable(false),
            ],
            $this
        )->withRequest($this->container->ilias()->http()->request());
    }

    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): Generator {
        $sorting = $order->get();

        foreach (
            $this->getEvents(
                $range->getStart(),
                $range->getLength(),
                key($sorting),
                current($sorting)
            ) as $event
        ) {
            $action = (string) $this->target_url->withParameter($this->parameter_name, $event->getIdentifier());

            $is_successfully_processed = $event->getProcessingState() === Event::STATE_SUCCEEDED;

            $thumbnail = $this->ui_factory->symbol()->icon()->custom(
                $event->publications()->getThumbnailUrl(),
                $event->getTitle(),
                Icon::LARGE
            );

            // Thumbsnails are only linked if the event is successfully processed
            if ($is_successfully_processed) {
                $thumbnail = $thumbnail->withAdditionalOnLoadCode(
                    fn(string $id): string => "let img_link = document.getElementById('$id'); 
                    img_link.onclick = function() { window.location.href = '$action';};
                    img_link.style.cursor = 'pointer';
                    "
                );
            }

            // we have to resize the thumbnail to a fixed width of 220px
            $thumbnail = $thumbnail->withAdditionalOnLoadCode(fn(string $id): string => "
                        let img = document.getElementById('$id');
                        img.style.width = '220px';
                        img.style.height = 'auto';"
            );

            $select_action = $this->ui_factory->link()->standard(
                $this->container->translator()->translate("select"),
                $action
            );

            yield $row_builder->buildDataRow(
                $event->getIdentifier(),
                [
                    'preview' => $thumbnail,
                    'title' => $event->getTitle(),
                    'date' => $event->getStart(),
                    'series' => $this->getSeriesName($event),
                    'presenter' => implode(", ", $event->getPresenter()),
                    'status' => $event->getProcessingState(),
                    'action' => $is_successfully_processed ? $this->ui_renderer->render($select_action) : ''
                ]
            );
        }
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return count($this->getEvents());
    }

    public function asModal(
        Button $triggerer,
        URI $target_url,
        string $parameter_name = 'event_id'
    ): array {
        $round_trip = $this->ui_factory->modal()->roundtrip(
            $this->container->translator()->translate("publication_usage_md_type_select"),
            [
                $this->asItemGroup($target_url, $parameter_name)
            ],
            [],
            (string) $target_url,
        );

        $triggerer = $triggerer->withOnClick($round_trip->getShowSignal());

        return [$triggerer, $round_trip];
    }

    public function asInput(?Transformation $transformation = null): Input
    {
        $events = $this->getEvents();

        $options = [];
        foreach ($events as $event) {
            $identifier = $event->getIdentifier();

            $options[$identifier] = sprintf(
                '%s: %s - %s',
                $this->getSeriesName($event),
                $event->getTitle(),
                substr($identifier, 0, 5),
            );
        }
        // sort by title
        asort($options);

        return $this->ui_factory->input()->field()->select(
            $this->container->translator()->translate("publication_usage_md_type_select"),
            $options
        )->withAdditionalTransformation($this->buildTrafo($transformation))->withRequired(true);
    }

    private function buildTrafo(?Transformation $transformation = null): Transformation
    {
        if ($transformation !== null) {
            return $transformation;
        }

        return $this->refinery->custom()->transformation(fn($value) => $value);
    }

    /**
     * @return Event[]
     */
    private function getEvents(
        int $offset = 0,
        int $limit = 1000,
        string $sort = 'title',
        string $order = 'ASC'
    ): array {
        if ($this->event_cache !== null) {
            return $this->event_cache;
        }

        // the api doesn't deliver a max count, so we fetch (limit + 1) to see if there should be a 'next' page
        try {
            $xoct_user = xoctUser::getInstance($this->container->ilias()->user());
            $identifier = $xoct_user->getIdentifier();
            if ($identifier === '') {
                return [];
            }

            $filter = $this->filter_data ?? [];
            $filter = array_filter($filter, static fn($value): bool => $value !== '');
            $filter['status'] = 'EVENTS.EVENTS.STATUS.PROCESSED';

            $sort__by_series = false;
            if ($sort === 'series') {
                $sort = 'title';
                $sort__by_series = true;
            }

            $events = (array) $this->event_repository->getFiltered(
                $filter,
                '',
                [$xoct_user->getUserRoleName()],
                $offset,
                $limit,
                "$sort:$order",
                true
            );
        } catch (\Throwable) {
            return [];
        }

        if ($sort__by_series) {
            usort($events, static function (Event $a, Event $b) use ($order) {
                return $order === 'DESC' ? strnatcasecmp($a->getSeries(), $b->getSeries()) : strnatcasecmp(
                    $b->getSeries(), $a->getSeries()
                );
            });
        }
        // we cannot filter by processing state here, as the api does not deliver this information directly ant this
        // would lead to non mathcing amount of rows. e.g. if 5 events should be displayed, but only 3 are processed,
        // only 3 would be displayed. pagination would not work correctly then.

        /*return array_filter($events, static function (Event $event): bool {
            return $event->getProcessingState() === Event::STATE_SUCCEEDED;
        });*/

        return $events;
    }
}
