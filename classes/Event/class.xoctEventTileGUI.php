<?php

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class xoctEventTileGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventTileGUI
{

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    const GET_PAGE = 'page';

    /**
     * @var xoctEventGUI
     */
    protected $parent_gui;
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var bool
     */
    protected $has_scheduled_events = false;
    /**
     * @var Event[]
     */
    protected $events;
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @var int
     */
    protected $page = 0;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var array
     */
    private $data;

    public function __construct(xoctEventGUI $parent_gui, ObjectSettings $objectSettings, array $data)
    {
        $this->parent_gui = $parent_gui;
        $this->objectSettings = $objectSettings;
        $this->factory = self::dic()->ui()->factory();
        $this->renderer = self::dic()->ui()->renderer();
        $this->page = (int)filter_input(INPUT_GET, self::GET_PAGE) ?: $this->page;
        $this->limit = xoctUserSettings::getTileLimitForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'));
        $this->events = array_map(function ($item) {
            return $item['object'];
        }, $data);
    }

    /**
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getHTML()
    {
        $container_tpl = self::plugin()->template('default/tpl.tile_container.html');
        $container_tpl->setVariable('LIMIT_SELECTOR', $this->getLimitSelectorHTML());
        $container_tpl->setVariable('PAGINATION_TOP', $this->getPaginationHTML());
        $container_tpl->setVariable('PAGINATION_BOTTOM', $this->getPaginationHTML());

        $from = $this->page * $this->limit;
        $to = ($this->page + 1) * $this->limit;
        for ($i = $from; $i < $to && isset($this->events[$i]); $i++) {
            $event = $this->events[$i];
            $event_renderer = new xoctEventRenderer($event, $this->objectSettings);

            $dropdown = $this->factory->dropdown()->standard($event_renderer->getActions());

            $image = $this->factory->image()->standard(
                $event->publications()->getThumbnailUrl(),
                "Thumbnail");

            $tile_tpl = self::plugin()->template('default/tpl.event_tile.html');
            $event_renderer->insertTitle($tile_tpl);
            $event_renderer->insertState($tile_tpl);

            $buttons_tpl = self::plugin()->template('default/tpl.event_buttons.html');
            $event_renderer->insertPlayerLink($buttons_tpl, 'link', 'LINK', 'btn-default');
            if (!$this->objectSettings->getStreamingOnly()) {
                $event_renderer->insertDownloadLink($buttons_tpl, 'link', 'LINK', 'btn-default');
            }
            if ($this->objectSettings->getUseAnnotations()) {
                $event_renderer->insertAnnotationLink($buttons_tpl, 'link', 'LINK', 'btn-default');
            }
            $tile_tpl->setVariable('EVENT_BUTTONS', $buttons_tpl->get());

            $card = $this->factory->card()->repositoryObject(
                $tile_tpl->get(),
                $image
            )->withActions(
                $dropdown
            );

            $sections = [];

            $property_list = [];
            $property_list[self::dic()->language()->txt('date')] = $event_renderer->getStartHTML();
            if ($this->objectSettings->getPermissionPerClip()) {
                $property_list[self::dic()->language()->txt('owner')] = $event_renderer->getOwnerHTML();
            }
            $sections[] = $this->factory->listing()->descriptive($property_list);

            $card = $card->withSections($sections);

            $container_tpl->setCurrentBlock('tile');
            $container_tpl->setVariable('TILE', $this->renderer->renderAsync($card));
            $container_tpl->parseCurrentBlock();
        }
        return $container_tpl->get();
    }


    /**
     * @param Event[] $events
     *
     * @return mixed
     */
    protected function sortData(array $events)
    {
        $tab_prop = new ilTablePropertiesStorage();

        $direction = $tab_prop->getProperty(xoctEventTableGUI::getGeneratedPrefix($this->parent_gui->getObjId()), self::dic()->user()->getId(), 'direction')
            ?? 'asc';
        $order = $tab_prop->getProperty(xoctEventTableGUI::getGeneratedPrefix($this->parent_gui->getObjId()), self::dic()->user()->getId(), 'order')
            ?? 'start';
        switch ($order) {
            case 'start_unix':
                $order = 'start';
                break;
            case 'created_unix':
                $order = 'created';
                break;
        }

        $events = ilUtil::sortArray(
            $events,
            $order,
            $direction
        );

        return $events;
    }

    /**
     * @return bool
     */
    public function hasScheduledEvents()
    {
        return $this->has_scheduled_events;
    }


    /**
     * @return string
     */
    protected function getPaginationHTML()
    {
        $max_count = count($this->events);
        $pages = ($max_count == 0) ? 1 : (int)ceil($max_count / $this->limit);
        $pagination = $this->factory->viewControl()->pagination()
            ->withMaxPaginationButtons($pages)
            ->withTotalEntries($max_count)
            ->withCurrentPage($this->page)
            ->withTargetURL(self::dic()->ctrl()->getLinkTarget($this->parent_gui, xoctEventGUI::CMD_STANDARD, '', true), self::GET_PAGE)
            ->withPageSize($this->limit);

        return $this->renderer->renderAsync($pagination);
    }

    /**
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function getLimitSelectorHTML()
    {
        $tpl = self::plugin()->template('default/tpl.tile_limit_selector.html');
        $tpl->setVariable('LIMIT_SELECTOR_FORM_ACTION', self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CHANGE_TILE_LIMIT));
        $select_input = new ilSelectInputGUI(self::plugin()->translate('tiles_per_page'), 'tiles_per_page');
        $select_input->setOptions([4 => 4, 8 => 8, 12 => 12, 16 => 16]);
        $select_input->setValue($this->limit);
        $tpl->setVariable('LABEL_LIMIT_SELECTOR', self::plugin()->translate('tiles_per_page'));
        $tpl->setVariable('LIMIT_SELECTOR', $select_input->getToolbarHTML());

        return $tpl->get();
    }
}
