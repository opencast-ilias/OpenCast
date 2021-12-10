<?php

use ILIAS\UI\Component\Component;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\UI\Modal\EventModals;

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
	 * @var Event
	 */
	protected $event;
	/**
	 * @var null | ObjectSettings
	 */
	protected $objectSettings;
	/**
	 * @var Factory
	 */
	protected $factory;
	/**
	 * @var Renderer
	 */
	protected $renderer;
    /**
     * @var EventModals
     */
	protected static $modals;

	public function __construct(Event $event, ?ObjectSettings $objectSettings = null)
    {
		$this->event = $event;
		$this->objectSettings = $objectSettings;
		$this->factory = self::dic()->ui()->factory();
		$this->renderer = self::dic()->ui()->renderer();
	}


    /**
     * @param EventModals $modals
     */
	public static function initModals(EventModals $modals)
    {
        self::$modals = $modals;
    }

	/**
	 * @param $tpl ilTemplate
	 * @param $variable string
	 * @param $value string
	 * @param string $block_title string
	 */
	public function insert(&$tpl, $variable, $value, $block_title = '') {
		if ($block_title) {
			$tpl->setCurrentBlock($block_title);
		}

		$tpl->setVariable($variable, $value);

		if ($block_title) {
			$tpl->parseCurrentBlock();
		}
	}


    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     *
     * @throws xoctException
     */
	public function insertThumbnail(&$tpl, $block_title = 'thumbnail', $variable = 'THUMBNAIL') {
		$this->insert($tpl, $variable, $this->getThumbnailHTML(), $block_title);
	}


    /**
     * @return string
     * @throws xoctException
     */
	public function getThumbnailHTML() {
		return $this->renderer->render($this->factory->image()->responsive($this->event->publications()->getThumbnailUrl(), 'Thumbnail'));
	}


    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function insertPlayerLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info') {
		if ($player_link_html = $this->getPlayerLinkHTML($button_type)) {
			$this->insert($tpl, $variable, $player_link_html, $block_title);
		}
	}


    /**
     * @param string $button_type
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function getPlayerLinkHTML($button_type = 'btn-info') {
		if ($this->isEventAccessible() && !is_null($this->event->publications()->getPlayerPublication())) {
			$link_tpl = self::plugin()->template('default/tpl.player_link.html');
			$link_tpl->setVariable('LINK_TEXT', self::plugin()->translate($this->event->isLiveEvent() ? 'player_live' : 'player', self::LANG_MODULE));
			$link_tpl->setVariable('BUTTON_TYPE', $button_type);
			$link_tpl->setVariable('TARGET', '_blank');
			if (xoctConf::getConfig(xoctConf::F_USE_MODALS)) {
				$modal = $this->getPlayerModal();
				$link_tpl->setVariable('LINK_URL', '#');
				$link_tpl->setVariable('MODAL', $modal->getHTML());
				$link_tpl->setVariable('MODAL_LINK', $this->getModalLink());
			} else {
				$link_tpl->setVariable('LINK_URL', $this->getInternalPlayerLink());
			}

			return $link_tpl->get();
		} else {
			return '';
		}
	}

    /**
     * @return string
     */
	public function getInternalPlayerLink() : string
    {
        self::dic()->ctrl()->clearParametersByClass(xoctEventGUI::class);
        self::dic()->ctrl()->setParameterByClass(xoctEventGUI::class, xoctEventGUI::IDENTIFIER, $this->event->getIdentifier());
        return self::dic()->ctrl()->getLinkTargetByClass(
            [
                ilRepositoryGUI::class,
                ilObjOpenCastGUI::class,
                xoctEventGUI::class,
                xoctPlayerGUI::class
            ], xoctPlayerGUI::CMD_STREAM_VIDEO);
    }


    /**
     * @return ilModalGUI
     * @throws xoctException
     */
	public function getPlayerModal() {
        $modal = ilModalGUI::getInstance();
        $modal->setId('modal_' . $this->event->getIdentifier());
        $modal->setHeading($this->event->getTitle());
        $modal->setBody('<iframe class="xoct_iframe" allowfullscreen="true" src="' . $this->getInternalPlayerLink() . '" style="border:none;"></iframe><br>');
        return $modal;
    }


    /**
     * @return string
     */
    public function getModalLink() {
	    return 'data-toggle="modal" data-target="#modal_' . $this->event->getIdentifier() . '"';
    }


    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function insertDownloadLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info') {
		if ($download_link_html = $this->getDownloadLinkHTML($button_type)) {
			$this->insert($tpl, $variable, $download_link_html, $block_title);
		}
	}


    /**
     * @param string $button_type
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function getDownloadLinkHTML($button_type = 'btn_info') {
        $download_dtos = $this->event->publications()->getDownloadDtos(false);
		if (($this->event->getProcessingState() == Event::STATE_SUCCEEDED) && (count($download_dtos) > 0)) {
			if ($this->objectSettings instanceof ObjectSettings && $this->objectSettings->getStreamingOnly()) {
				return '';
			}
            $multi = (new PublicationUsageRepository())->getUsage(PublicationUsage::USAGE_DOWNLOAD)->isAllowMultiple();
			if ($multi) {
                $items = array_map(function($dto) {
                    self::dic()->ctrl()->setParameterByClass(xoctEventGUI::class, 'event_id', $this->event->getIdentifier());
                    self::dic()->ctrl()->setParameterByClass(xoctEventGUI::class, 'pub_id', $dto->getPublicationId());
                    return $this->factory->link()->standard(
                        $dto->getResolution(),
                        self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_DOWNLOAD)
                    );
                }, $download_dtos);
                $dropdown = $this->factory->dropdown()->standard(
			        $items
                )->withLabel(self::plugin()->translate('download', self::LANG_MODULE));
			    return self::dic()->ui()->renderer()->renderAsync($dropdown);
            } else {
                self::dic()->ctrl()->setParameterByClass(xoctEventGUI::class, 'event_id', $this->event->getIdentifier());
                $link = self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_DOWNLOAD);
                $link_tpl = self::plugin()->template('default/tpl.player_link.html');
                $link_tpl->setVariable('TARGET', '_self');
                $link_tpl->setVariable('BUTTON_TYPE', $button_type);
                $link_tpl->setVariable('LINK_TEXT', self::plugin()->translate('download', self::LANG_MODULE));
                $link_tpl->setVariable('LINK_URL', $link);

                return $link_tpl->get();
            }
		} else {
			return '';
		}
	}


    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function insertAnnotationLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info') {
		if ($annotation_link_html = $this->getAnnotationLinkHTML($button_type)) {
			$this->insert($tpl, $variable, $annotation_link_html, $block_title);
		}
	}


    /**
     * @param string $button_type
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
	public function getAnnotationLinkHTML($button_type = 'btn_info') {
		if (($this->event->getProcessingState() == Event::STATE_SUCCEEDED)
            && ($this->event->publications()->getAnnotationPublication()))
		{
            self::dic()->ctrl()->setParameterByClass(xoctEventGUI::class, xoctEventGUI::IDENTIFIER, $this->event->getIdentifier());
            $annotations_link = self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_ANNOTATE);
			$link_tpl = self::plugin()->template('default/tpl.player_link.html');
            $link_tpl->setVariable('TARGET', '_blank');
            $link_tpl->setVariable('BUTTON_TYPE', $button_type);
			$link_tpl->setVariable('LINK_TEXT', self::plugin()->translate('annotate', self::LANG_MODULE));
			$link_tpl->setVariable('LINK_URL', $annotations_link);

			return $link_tpl->get();
		} else {
			return '';
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
		$this->insert($tpl, $variable, $this->getTitleHTML(), $block_title);
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function insertDescription(&$tpl, $block_title = 'description', $variable = 'DESCRIPTION') {
		$this->insert($tpl, $variable, $this->getDescriptionHTML(), $block_title);
	}

	/**
	 * @return string
	 */
	public function getTitleHTML() {
		return $this->event->getTitle();
	}


    /**
     * @return string
     */
	public function getDescriptionHTML() {
	    return $this->event->getDescription();
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
		if ($state_html = $this->getStateHTML()) {
			$this->insert($tpl, $variable, $state_html, $block_title);
		}
	}

	/**
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function getStateHTML() {
		if (!$this->isEventAccessible()) {
		    $processing_state = $this->event->getProcessingState();
			$state_tpl = self::plugin()->template('default/tpl.event_state.html');
			$state_tpl->setVariable('STATE_CSS', Event::$state_mapping[$processing_state]);

			$suffix = '';
			if ($this->event->isOwner(xoctUser::getInstance(self::dic()->user()))
				&& in_array($processing_state, array(
					Event::STATE_FAILED,
					Event::STATE_ENCODING
				))) {
				$suffix = '_owner';
			}

			$placeholders = [];
			if ($processing_state == Event::STATE_LIVE_SCHEDULED) {
                $placeholders[] = date(
                    'd.m.Y, H:i',
                    $this->event->getScheduling()->getStart()->getTimestamp() - (((int)xoctConf::getConfig(xoctConf::F_START_X_MINUTES_BEFORE_LIVE)) * 60)
                );
			}

            $state_tpl->setVariable('STATE', self::plugin()->translate('state_' . strtolower($processing_state) . $suffix, self::LANG_MODULE, $placeholders));

            return $state_tpl->get();
		} else {
			return '';
		}
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 */
	public function insertPresenter(&$tpl, $block_title = 'presenter', $variable = 'PRESENTER') {
		$this->insert($tpl, $variable, $this->getPresenterHTML(), $block_title);
	}

	/**
	 * @return String
	 */
	public function getPresenterHTML() {
		return $this->event->getPresenter() ?: '&nbsp';
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 */
	public function insertLocation(&$tpl, $block_title = 'location', $variable = 'LOCATION') {
		$this->insert($tpl, $variable, $this->getLocationHTML(), $block_title);
	}

	/**
	 * @return string
	 */
	public function getLocationHTML() {
		return $this->event->getLocation();
	}

	/**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @param string $format
	 */
	public function insertStart(&$tpl, $block_title = 'start', $variable = 'START', $format = 'd.m.Y - H:i') {
		$this->insert($tpl, $variable, $this->getStartHTML($format), $block_title);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function getStartHTML($format = 'd.m.Y - H:i') {
		return $this->event->getStart()->format($format);
	}

    /**
     * @param ilTemplate $tpl
     * @param string     $block_title
     * @param string     $variable
     */
    public function insertUnprotectedLink(
        ilTemplate &$tpl,
        string $block_title = 'unprotected_link',
        string $variable = 'UNPROTECTED_LINK'
    ) {
        $link_tpl = self::plugin()->template('default/tpl.event_link.html');
        $link = $this->event->publications()->getUnprotectedLink() ?: '';
        $link_tpl->setVariable('URL', $link);
        $link_tpl->setVariable('TOOLTIP_TEXT', self::plugin()->translate('tooltip_copy_link'));
        $this->insert($tpl, $variable, $link ? $link_tpl->get() : '', $block_title);
    }

    /**
	 * @param $tpl ilTemplate
	 * @param string $block_title
	 * @param string $variable
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function insertOwner(&$tpl, $block_title = 'owner', $variable = 'OWNER') {
		$this->insert($tpl, $variable, $this->getOwnerHTML(), $block_title);
	}


    /**
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function getOwnerHTML() {
		$owner_tpl = self::plugin()->template('default/tpl.event_owner.html');
		$owner_tpl->setVariable('OWNER', $this->event->getOwnerUsername());

		if ($this->objectSettings instanceof ObjectSettings && $this->objectSettings->getPermissionPerClip()) {
			$owner_tpl->setCurrentBlock('invitations');
			$in = xoctInvitation::getActiveInvitationsForEvent($this->event, $this->objectSettings, true);
			if ($in > 0) {
				$owner_tpl->setVariable('INVITATIONS', $in);
			}
			$owner_tpl->parseCurrentBlock();
		}

		return $owner_tpl->get();
	}


    /**
     * @return bool
     */
    protected function isEventAccessible() {
	    $processing_state = $this->event->getProcessingState();

	    if ($processing_state == Event::STATE_SUCCEEDED) {
	        return true;
        }

	    if ($this->event->isLiveEvent()) {
	        if ($processing_state == Event::STATE_LIVE_RUNNING) {
	            return true;
            }
	        if ($processing_state == Event::STATE_LIVE_SCHEDULED) {
	            $start = $this->event->getScheduling()->getStart()->getTimestamp();
                $accessible_before_start = ((int)xoctConf::getConfig(xoctConf::F_START_X_MINUTES_BEFORE_LIVE)) * 60;
                $accessible_from = $start - $accessible_before_start;
                $accessible_to = $this->event->getScheduling()->getEnd()->getTimestamp();
	            return ($accessible_from < time()) && ($accessible_to > time());
            }
        }

        return false;
    }

    /**
     * @return Component[]
     * @throws DICException
     */
    public function getActions() : array
    {
        if (!in_array($this->event->getProcessingState(), array(
            Event::STATE_SUCCEEDED,
            Event::STATE_NOT_PUBLISHED,
            Event::STATE_READY_FOR_CUTTING,
            Event::STATE_OFFLINE,
            Event::STATE_FAILED,
            Event::STATE_SCHEDULED,
            Event::STATE_SCHEDULED_OFFLINE,
            Event::STATE_LIVE_RUNNING,
            Event::STATE_LIVE_SCHEDULED,
            Event::STATE_LIVE_OFFLINE,
        ))) {
            return [];
        }
        /**
         * @var $xoctUser xoctUser
         */
        $xoctUser = xoctUser::getInstance(self::dic()->user());

        self::dic()->ctrl()->setParameterByClass(
            xoctEventGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );
        self::dic()->ctrl()->setParameterByClass(
            xoctInvitationGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );
        self::dic()->ctrl()->setParameterByClass(
            xoctChangeOwnerGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );

        $actions = [];

        if (ilObjOpenCast::DEV) {
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate('event_view'),
                self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_VIEW)
            );
        }

        // Edit Owner
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_OWNER, $this->event, $xoctUser, $this->objectSettings)) {
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate('event_edit_owner'),
                self::dic()->ctrl()->getLinkTargetByClass(xoctChangeOwnerGUI::class, xoctChangeOwnerGUI::CMD_STANDARD)
            );
        }

        // Share event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SHARE_EVENT, $this->event, $xoctUser, $this->objectSettings)) {
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate('event_invite_others'),
                self::dic()->ctrl()->getLinkTargetByClass(xoctInvitationGUI::class, xoctInvitationGUI::CMD_STANDARD)
            );
        }

        // Cut Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $this->event, $xoctUser)) {
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate('event_cut'),
                self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CUT)
            )->withOpenInNewViewport(true);
        }

        // Republish
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $this->event, $xoctUser)
            && !$this->event->isScheduled() && !is_null(self::$modals) && !is_null(self::$modals->getRepublishModal())
        ) {
            $actions[] = $this->factory->button()->shy(
                self::plugin()->translate('event_republish'),
                self::$modals->getRepublishModal()->getShowSignal()
            )->withOnLoadCode(function ($id) {
                return "$({$id}).on('click', function(event){ $('input#republish_event_id').val('{$this->event->getIdentifier()}'); });";
            });
        }

        // Online/offline
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SET_ONLINE_OFFLINE, $this->event, $xoctUser)) {
            if ($this->event->getXoctEventAdditions()->getIsOnline()) {
                $actions[] = $this->factory->link()->standard(
                    self::plugin()->translate('event_set_offline'),
                    self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_SET_OFFLINE)
                );
            } else {
                $actions[] = $this->factory->link()->standard(
                    self::plugin()->translate('event_set_online'),
                    self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_SET_ONLINE)
                );
            }

        }

        // Delete Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $this->event, $xoctUser)) {
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate('event_delete'),
                self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CONFIRM)
            );
        }

        // Edit Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $this->event, $xoctUser)) {
            // show different langvar when date is editable
            $lang_var = ($this->event->isScheduled()
                && (xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::ALL_METADATA)) ?
                'event_edit_date'  : 'event_edit';
            $actions[] = $this->factory->link()->standard(
                self::plugin()->translate($lang_var),
                self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class,
                    $this->event->isScheduled() ? xoctEventGUI::CMD_EDIT_SCHEDULED : xoctEventGUI::CMD_EDIT)
            );
        }

        // Report Quality
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $this->event)
            && !is_null(self::$modals) && !is_null(self::$modals->getReportQualityModal())
        ) {
            $actions[] = $this->factory->button()->shy(
                self::plugin()->translate('event_report_quality_problem'),
                self::$modals->getReportQualityModal()->getShowSignal()
            )->withOnLoadCode(function ($id) {
                return "$({$id}).on('click', function(event){ $('input#xoct_report_quality_event_id').val('{$this->event->getIdentifier()}');$('#xoct_report_quality_modal textarea#message').focus(); });";
            });
        }

        return $actions;
    }
}
