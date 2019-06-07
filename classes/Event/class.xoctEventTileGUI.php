<?php

use ILIAS\UI\Factory;
use ILIAS\UI\Implementation\Component\Signal;
use ILIAS\UI\Implementation\Component\SignalGenerator;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;

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
	protected $limit = 12;

	/**
	 * xoctEventTileGUI constructor.
	 * @param $parent_gui xoctEventGUI
	 * @param $xoctOpenCast xoctOpenCast
	 */
	public function __construct($parent_gui, $xoctOpenCast) {
		$this->parent_gui = $parent_gui;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->factory = self::dic()->ui()->factory();
		$this->renderer = self::dic()->ui()->renderer();
		$this->page = (int) filter_input(INPUT_GET, self::GET_PAGE) ?: $this->page;
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
		$container_tpl->setVariable('PAGINATION_TOP', $this->getPaginationHTML());
		$container_tpl->setVariable('PAGINATION_BOTTOM', $this->getPaginationHTML());
		$icon = $this->factory->icon()->custom(self::plugin()->directory() . '/templates/images/icon_video.svg', 'Video');

		$from = $this->page * $this->limit;
		$to = ($this->page + 1) * $this->limit;
		for ($i = $from; $i < $to && isset($this->events[$i]); $i++) {
			$xoctEvent = $this->events[$i];
			$event_renderer = new xoctEventRenderer($xoctEvent);

			$dropdown = $this->factory->dropdown()->standard($this->getActionButtons($xoctEvent));

			$image = $this->factory->image()->responsive(
				$xoctEvent->getThumbnailUrl(),
				"Thumbnail");

			$tile_tpl = self::plugin()->template('default/tpl.event_tile.html');
			$event_renderer->insertTitleAndState($tile_tpl);
			$event_renderer->insertPlayerLink($tile_tpl);
			$event_renderer->insertDownloadLink($tile_tpl);
			$event_renderer->insertAnnotationLink($tile_tpl);

			$card = $this->factory->card()->repositoryObject(
				$tile_tpl->get(),
				$image
			)->withActions(
				$dropdown
			)->withObjectIcon(
				$icon
			);

			$container_tpl->setCurrentBlock('tile');
			$container_tpl->setVariable('TILE', $this->renderer->render($card));
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
		$xoctEvents = xoctEvent::getFiltered(['series' => $this->xoctOpenCast->getSeriesIdentifier()], null, null, $this->page, $this->limit, true);
		foreach ($xoctEvents as $key => $xoctEvent) {
			if (!ilObjOpenCastAccess::hasReadAccessOnEvent($xoctEvent, $xoctUser, $this->xoctOpenCast)) {
				unset($xoctEvents[$key]);
			} elseif ($xoctEvent->isScheduled()) {
				$this->has_scheduled_events = true;
			}
		}
		$this->events = $xoctEvents;
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
			$button = ilLinkButton::getInstance();
			$button->setCaption(self::plugin()->translate($action['lang_var'] ?: $key), false);
			$button->setUrl($action['link']);
			$button->setTarget($action['frame']);
			if (isset($action['onclick'])) {
				$button->setOnClick($action['onclick']);
			}
			$dropdown_items[] = $this->factory->legacy($button->getToolbarHTML());
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

}