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
		if (($this->xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED) && ($player_link = $this->xoctEvent->getPlayerLink())) {
			if ($block_title) {
				$tpl->setCurrentBlock($block_title);
			}
			$link_tpl = self::plugin()->template('default/tpl.player_link.html');
			$link_tpl->setVariable('LINK_TEXT', self::plugin()->translate('player', self::LANG_MODULE));
			if (xoctConf::getConfig(xoctConf::F_USE_MODALS)) {
				$modal = ilModalGUI::getInstance();
				$modal->setId('modal_' . $this->xoctEvent->getIdentifier());
				$modal->setHeading($this->xoctEvent->getTitle());
				$modal->setBody('<iframe class="xoct_iframe" src="' . $player_link . '"></iframe>');
				$link_tpl->setVariable('MODAL', $modal->getHTML());
				$link_tpl->setVariable('MODAL_LINK', 'data-toggle="modal" data-target="#modal_' . $this->xoctEvent->getIdentifier() . '"');
				$link_tpl->setVariable('LINK_URL', '#');
			} else {
				$link_tpl->setVariable('LINK_URL', $player_link);
			}

			$tpl->setVariable($variable, $link_tpl->get());

			if ($block_title) {
				$tpl->parseCurrentBlock();
			}
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

			if ($block_title) {
				$tpl->setCurrentBlock($block_title);
			}

			$link_tpl = self::plugin()->template('default/tpl.player_link.html');
			$link_tpl->setVariable('LINK_TEXT', self::plugin()->translate('download', self::LANG_MODULE));
			$link_tpl->setVariable('LINK_URL', $download_link);

			$tpl->setVariable($variable, $link_tpl->get());

			if ($block_title) {
				$tpl->parseCurrentBlock();
			}
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

			if ($block_title) {
				$tpl->setCurrentBlock($block_title);
			}

			$annotations_link = self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_ANNOTATE);
			$link_tpl = self::plugin()->template('default/tpl.player_link.html');
			$link_tpl->setVariable('LINK_TEXT', self::plugin()->translate('annotate', self::LANG_MODULE));
			$link_tpl->setVariable('LINK_URL', $annotations_link);

			$tpl->setVariable($variable, $link_tpl->get());

			if ($block_title) {
				$tpl->parseCurrentBlock();
			}
		}

	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function insertTitle(&$tpl, $block_title = 'title', $variable = 'TITLE') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $this->xoctEvent->getTitle());

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param $tpl
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function insertState(&$tpl, $block_title = 'state', $variable = 'STATE') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$state_tpl = self::plugin()->template('default/tpl.event_state.html');
		$state_tpl->setVariable('STATE_CSS', xoctEvent::$state_mapping[$this->xoctEvent->getProcessingState()]);

		if ($this->xoctEvent->getProcessingState() != xoctEvent::STATE_SUCCEEDED) {
			$owner = $this->xoctEvent->isOwner(xoctUser::getInstance(self::dic()->user()))
			&& in_array($this->xoctEvent->getProcessingState(), array(
				xoctEvent::STATE_FAILED,
				xoctEvent::STATE_ENCODING
			)) ? '_owner' : '';
			$state_tpl->setVariable('STATE', self::plugin()->translate('state_' . strtolower($this->xoctEvent->getProcessingState()) . $owner, self::LANG_MODULE));
		}

		$tpl->setVariable($variable, $state_tpl->get());

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