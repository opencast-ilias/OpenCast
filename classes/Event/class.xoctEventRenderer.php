<?php

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;

/**
 * Class xoctEventRenderer
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventRenderer {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
	const LANG_MODULE = 'event';

	/**
	 * @var xoctEvent
	 */
	protected $xoctEvent;
	/**
	 * @var null | xoctOpenCast
	 */
	protected $xoctOpenCast;
	/**
	 * @var Factory
	 */
	protected $factory;
	/**
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * xoctEventRenderer constructor.
	 * @param $xoctEvent xoctEvent
	 * @param null $xoctOpenCast
	 */
	public function __construct($xoctEvent, $xoctOpenCast = null) {
		$this->xoctEvent = $xoctEvent;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->factory = self::dic()->ui()->factory();
		$this->renderer = self::dic()->ui()->renderer();
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 */
	public function insertThumbnail(&$tpl, $block_title = 'thumbnail', $variable = 'THUMBNAIL') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $this->renderer->render($this->factory->image()->responsive($this->xoctEvent->getThumbnailUrl(), 'Thumbnail')));

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function insertPlayerLink(&$tpl, $block_title = 'link', $variable = 'LINK') {
		$is_live = $this->xoctEvent->isLiveEvent();
		if (($this->xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED || $is_live) && ($player_link = $this->xoctEvent->getPlayerLink())) {
			if (xoctConf::getConfig(xoctConf::F_USE_MODALS)) {
				$modal = ilModalGUI::getInstance();
				$modal->setId('modal_' . $this->xoctEvent->getIdentifier());
				$modal->setHeading($this->xoctEvent->getTitle());
				$modal->setBody('<iframe class="xoct_iframe" src="' . $player_link . '"></iframe>');
				$link_html = $this->getLinkHTML(self::plugin()->translate($is_live ? 'player_live' : 'player', self::LANG_MODULE), '#', $modal->getHTML(), 'data-toggle="modal" data-target="#modal_' . $this->xoctEvent->getIdentifier() . '"');
			} else {
				$link_html = $this->getLinkHTML(self::plugin()->translate($is_live ? 'player_live' : 'player', self::LANG_MODULE), $player_link);
			}

			$this->insertHTML($tpl, $block_title, $variable, $link_html);
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function insertDownloadLink(&$tpl, $block_title = 'link', $variable = 'LINK') {
		if (($this->xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED) && ($download_link = $this->xoctEvent->getDownloadLink())) {
			if ($this->xoctOpenCast instanceof  xoctOpenCast && $this->xoctOpenCast->getStreamingOnly()) {
				return;
			}
			$this->insertHTML($tpl, $block_title, $variable, $this->getLinkHTML(self::plugin()->translate('download', self::LANG_MODULE), $download_link));
		}

	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function insertAnnotationLink(&$tpl, $block_title = 'link', $variable = 'LINK') {
		if (($this->xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED) && ($this->xoctEvent->getAnnotationLink())) {
			if ($this->xoctOpenCast instanceof xoctOpenCast && !$this->xoctOpenCast->getUseAnnotations()) {
				return;
			}

			$annotations_link = self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_ANNOTATE);
			$this->insertHTML($tpl, $block_title, $variable, $this->getLinkHTML(self::plugin()->translate('annotate', self::LANG_MODULE), $annotations_link));
		}

	}

	/**
	 * @param $tpl
	 * @param $block_title
	 * @param $variable
	 * @param $html
	 */
	protected function insertHTML(&$tpl, $block_title, $variable, $html) {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $html);

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $link_text
	 * @param $link_url
	 * @param string $modal_html
	 * @param string $modal_link
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	protected function getLinkHTML($link_text, $link_url, $modal_html = '', $modal_link = '') {
		$link_tpl = self::plugin()->template('default/tpl.player_link.html');
		$link_tpl->setVariable('LINK_TEXT', $link_text);
		$link_tpl->setVariable('LINK_URL', $link_url);
		if ($modal_html) {
			$link_tpl->setVariable('MODAL', $modal_html);
		}
		if ($modal_link) {
			$link_tpl->setVariable('MODAL_LINK', $modal_link);
		}
		return $link_tpl->get();
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function insertTitleAndState(&$tpl, $block_title = 'title', $variable = 'TITLE') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$title_tpl = self::plugin()->template('default/tpl.event_title.html');
		$title_tpl->setVariable('TITLE', $this->xoctEvent->getTitle());
		$title_tpl->setVariable('STATE_CSS', xoctEvent::$state_mapping[$this->xoctEvent->getProcessingState()]);

		if ($this->xoctEvent->getProcessingState() != xoctEvent::STATE_SUCCEEDED) {
			$suffix = '';
			if ($this->xoctEvent->isOwner(xoctUser::getInstance(self::dic()->user()))
				&& in_array($this->xoctEvent->getProcessingState(), array(
					xoctEvent::STATE_FAILED,
					xoctEvent::STATE_ENCODING
				))) {
				$suffix = '_owner';
			} else if ($this->xoctEvent->isLiveEvent()) {
				$suffix = '_live';
			}
			$title_tpl->setVariable('STATE', self::plugin()->translate('state_' . strtolower($this->xoctEvent->getProcessingState()) . $suffix, self::LANG_MODULE));
		}

		$tpl->setVariable($variable, $title_tpl->get());

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 */
	public function insertPresenter(&$tpl, $block_title = 'presenter', $variable = 'PRESENTER') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $this->xoctEvent->getPresenter());

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 */
	public function insertLocation(&$tpl, $block_title = 'location', $variable = 'LOCATION') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $this->xoctEvent->getLocation());

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @param string $format
	 */
	public function insertStart(&$tpl, $block_title = 'start', $variable = 'START', $format = 'd.m.Y - H:i:s') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $this->xoctEvent->getStart()->format($format));

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function insertOwner(&$tpl, $block_title = 'owner', $variable = 'OWNER') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$owner_tpl = self::plugin()->template('default/tpl.event_owner.html');
		$owner_tpl->setVariable('OWNER', $this->xoctEvent->getOwnerUsername());

		if ($this->xoctOpenCast instanceof xoctOpenCast && $this->xoctOpenCast->getPermissionPerClip()) {
			$owner_tpl->setCurrentBlock('invitations');
			$in = xoctInvitation::getActiveInvitationsForEvent($this->xoctEvent, $this->xoctOpenCast, true);
			if ($in > 0) {
				$owner_tpl->setVariable('INVITATIONS', $in);
			}
			$owner_tpl->parseCurrentBlock();
		}

		$tpl->setVariable($variable, $owner_tpl->get());

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}


}