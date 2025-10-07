<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use ILIAS\UI\Component\Listing\Entity\DataRetrieval;
use ILIAS\UI\Component\Listing\Entity\Mapping;
use ILIAS\Data\Range;
use ILIAS\Data\URI;
use ILIAS\DI\UIServices;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionParameter;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTarget;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Series implements DataRetrieval
{
    use Commons;

    private const DEFAULT_PAGE_SIZE = 6;
    private const SORT_TITLE_ASC = 'title:asc';
    private const SORT_DATE_ASC = 'date:asc';
    private EventAPIRepository $event_repository;
    private SeriesAPIRepository $series_repository;
    private ?\srag\Plugins\Opencast\Model\Series\Series $series = null;
    private Factory $ui_factory;
    private UIServices $ui;
    private int $total = 0;

    public function __construct(
        private Container $container,
        private Events $events,
        private SeriesActionTargetResolver $resolver
    ) {
        $this->ui = $this->container->ilias()->ui();
        $this->ui_factory = $this->container->ilias()->ui()->factory();
        $this->series_repository = $this->container->get(SeriesAPIRepository::class);
        $this->event_repository = $this->container->get(EventAPIRepository::class);
    }

    protected function buildSeries(string $series_id): void
    {
        $this->series ??= $this->series_repository->find($series_id);
        if ($this->series === null) {
            throw new \InvalidArgumentException("Series with ID $series_id not found.");
        }
    }

    public function asEntityListWithFilter(
        string $series_id,
        URI $current_url,
        URI $target_url,
    ): \Generator {
        $this->buildSeries($series_id);
        // filter service
        global $DIC;

        // toto yield filters
        yield from $this->asEntityListInPanel($resolver, $series_id);
    }

    public function asEntityList(
        string $series_id
    ): \Generator {
        $this->buildSeries($series_id);
        $this->ui->mainTemplate()->addCss(
            "./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events10.css"
        );

        yield $this->ui_factory->listing()->entity()->standard($this->events)->withData($this);
        yield $this->events->getTooltips();
    }

    public function asEntityListInPanel(
        string $series_id,
        string $title = '',
    ): \Generator {
        $this->buildSeries($series_id);

        $entity_list = iterator_to_array($this->asEntityList($series_id));

        yield $this->ui_factory->panel()->secondary()->legacy(
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
        )->withViewControls([
            $this->ui_factory
                ->viewControl()
                ->pagination()
                ->withCurrentPage($this->resolver->resolveParameter(SeriesActionParameter::PAGE))
                ->withPageSize(self::DEFAULT_PAGE_SIZE)
                ->withTotalEntries($this->total)
                ->withTargetURL(
                    (string) $this->resolver->resolve(SeriesActionTarget::PAGE),
                    SeriesActionTarget::PAGE->value
                ),
            $this->ui_factory
                ->viewControl()
                ->sortation([self::SORT_TITLE_ASC => 'Title', self::SORT_DATE_ASC => 'Date'], self::SORT_TITLE_ASC)
                ->withSelected($this->resolver->resolveParameter(SeriesActionParameter::SORT) ?? self::SORT_TITLE_ASC)
                ->withTargetURL(
                    (string) $this->resolver->resolve(SeriesActionTarget::SORT),
                    SeriesActionTarget::SORT->value
                )
        ]);
        // drop first element as it is already rendered in panel
        array_shift($entity_list);

        yield $entity_list;
    }

    public function getEntities(Mapping $mapping, ?Range $range, ?array $additional_parameters): \Generator
    {
        $page = $this->resolver->resolveParameter(SeriesActionParameter::PAGE);
        $sort = $this->resolver->resolveParameter(SeriesActionParameter::SORT) ?? self::SORT_TITLE_ASC;

        $filtered = $this->event_repository->getFiltered(
            ['series' => $this->series->getIdentifier()],
            '',
            [],
            $page * self::DEFAULT_PAGE_SIZE,
            self::DEFAULT_PAGE_SIZE,
            $sort,
        );

        $this->total = count(
            $this->event_repository->getFiltered(
                ['series' => $this->series->getIdentifier()],
                '',
                []
            )
        );

        foreach ($filtered as $event) {
            yield $mapping->map($event);
        }
    }

}
