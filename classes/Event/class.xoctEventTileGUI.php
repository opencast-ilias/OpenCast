<?php

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;

/**
 * Class xoctEventTileGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventTileGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const GET_PAGE = 'page';

	/**
	 * @var xoctEventGUI
	 */
	protected $parent_gui;
	/**
	 * @var xoctOpenCast
	 */
	protected $xoctOpenCast;
	/**
	 * @var bool
	 */
	protected $has_scheduled_events = false;
	/**
	 * @var xoctEvent[]
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
     * @var EventRepository
     */
	protected $event_repository;

	/**
	 * xoctEventTileGUI constructor.
	 * @param $parent_gui xoctEventGUI
	 * @param $xoctOpenCast xoctOpenCast
	 */
	public function __construct($parent_gui, $xoctOpenCast) {
		$this->parent_gui = $parent_gui;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->event_repository = new EventRepository(self::dic()->dic());
		$this->factory = self::dic()->ui()->factory();
		$this->renderer = self::dic()->ui()->renderer();
		$this->page = (int) filter_input(INPUT_GET, self::GET_PAGE) ?: $this->page;
		$this->limit = xoctUserSettings::getTileLimitForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'));
	}

	/**
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function getHTML() {
		$this->parseData();

		$container_tpl = self::plugin()->template('default/tpl.tile_container.html');
		$container_tpl->setVariable('LIMIT_SELECTOR', $this->getLimitSelectorHTML());
		$container_tpl->setVariable('PAGINATION_TOP', $this->getPaginationHTML());
		$container_tpl->setVariable('PAGINATION_BOTTOM', $this->getPaginationHTML());

		$from = $this->page * $this->limit;
		$to = ($this->page + 1) * $this->limit;
		for ($i = $from; $i < $to && isset($this->events[$i]); $i++) {
			$xoctEvent = $this->events[$i];
			$event_renderer = new xoctEventRenderer($xoctEvent, $this->xoctOpenCast);

			$dropdown = $this->factory->dropdown()->standard($this->getActionButtons($xoctEvent));

			$image = $this->factory->image()->standard(
				$xoctEvent->getThumbnailUrl(),
				"Thumbnail");

			$tile_tpl = self::plugin()->template('default/tpl.event_tile.html');
			$event_renderer->insertTitle($tile_tpl);
			$event_renderer->insertState($tile_tpl);

			$buttons_tpl = self::plugin()->template('default/tpl.event_buttons.html');
			$event_renderer->insertPlayerLink($buttons_tpl, 'link', 'LINK', 'btn-default');
			$event_renderer->insertDownloadLink($buttons_tpl, 'link', 'LINK', 'btn-default');
			$event_renderer->insertAnnotationLink($buttons_tpl, 'link', 'LINK', 'btn-default');
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
			if ($this->xoctOpenCast->getPermissionPerClip()) {
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
	 * @return void
	 * @throws xoctException
	 */
	protected function parseData() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEvents = $this->event_repository->getFiltered(['series' => $this->xoctOpenCast->getSeriesIdentifier()], '', [], 0, 1000, '',true);
		foreach ($xoctEvents as $key => $xoctEvent) {
			if (!ilObjOpenCastAccess::hasReadAccessOnEvent($xoctEvent, $xoctUser, $this->xoctOpenCast)) {
				unset($xoctEvents[$key]);
			} elseif ($xoctEvent->isScheduled()) {
				$this->has_scheduled_events = true;
			}
		}
        $tab_prop = new ilTablePropertiesStorage();

        $order = $tab_prop->getProperty(xoctEventTableGUI::getGeneratedPrefix($this->xoctOpenCast), self::dic()->user()->getId(), 'order')
            ?? 'start';
        $direction = $tab_prop->getProperty(xoctEventTableGUI::getGeneratedPrefix($this->xoctOpenCast), self::dic()->user()->getId(), 'direction')
            ?? 'asc';
        usort($xoctEvents, function (xoctEvent $a, xoctEvent $b) use ($order, $direction) {
           switch ($order) {
               case 'start':
                   if ($direction == 'asc') {
                       return $b->getStart()->getTimestamp() - $a->getStart()->getTimestamp();
                   } else {
                       return $a->getStart()->getTimestamp() - $b->getStart()->getTimestamp();
                   }
               case 'title':
               case 'description':
               case 'presenter':
               case 'location':
               case 'owner':
                   $getter = 'get' . $order . ($order == 'owner' ? 'username' : '');
                    if ($direction == 'asc') {
                        return strcasecmp($a->{$getter}(), $b->{$getter}());
                    } else {
                        return strcasecmp($b->{$getter}(), $a->{$getter}());
                    }
               default:
                   return 0;
           }
        });
		$this->events = array_values($xoctEvents);
	}


	/**
	 * @return bool
	 */
	public function hasScheduledEvents() {
		return $this->has_scheduled_events;
	}

	/**
	 * @param xoctEvent $xoctEvent
	 * @return array
	 * @throws DICException
	 */
	protected function getActionButtons(xoctEvent $xoctEvent) {
		$dropdown_items = [];
		foreach ($xoctEvent->getActions($this->xoctOpenCast) as $key => $action) {
			if (isset($action['onclick'])) {
				$onclick = $action['onclick'];
				$dropdown_items[] = $this->factory->button()->shy(self::plugin()->translate($action['lang_var'] ?: $key), $action['link'])
					->withOnLoadCode(function ($id) use ($onclick) {
						return "$('#$id').on('click', function(){{$onclick}});";
				});
			} else {
				$dropdown_items[] = $this->factory->link()->standard(self::plugin()->translate($action['lang_var'] ?: $key), $action['link'])->withOpenInNewViewport((bool) $action['frame']);
			}
		}
		return $dropdown_items;
	}

	/**
	 * @return string
	 */
	protected function getPaginationHTML() {
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
	protected function getLimitSelectorHTML() {
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