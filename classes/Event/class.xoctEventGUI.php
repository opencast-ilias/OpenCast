<?php

use ILIAS\UI\Component\Modal\Modal;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Chat\GUI\ChatHistoryGUI;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;
use srag\Plugins\Opencast\Model\API\Group\Group;
use srag\Plugins\Opencast\Model\Config\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\UI\Input\EventFormGUI;
use srag\Plugins\Opencast\UI\Input\Plupload;
use srag\Plugins\Opencast\UI\Modal\EventModals;

/**
 * Class xoctEventGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_Calls xoctEventGUI: xoctPlayerGUI
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI {

	const IDENTIFIER = 'eid';
	const CMD_STANDARD = 'index';
	const CMD_CLEAR_CACHE = 'clearCache';
	const CMD_EDIT_OWNER = 'editOwner';
	const CMD_UPDATE_OWNER = 'updateOwner';
	const CMD_UPLOAD_CHUNKS = EventFormGUI::PARENT_CMD_UPLOAD_CHUNKS;
	const CMD_SET_ONLINE = 'setOnline';
	const CMD_SET_OFFLINE = 'setOffline';
	const CMD_CUT = 'cut';
	const CMD_ANNOTATE = 'annotate';
	const CMD_REPORT_DATE = 'reportDate';
	const CMD_REPORT_QUALITY = 'reportQuality';
	const CMD_SCHEDULE = 'schedule';
	const CMD_CREATE_SCHEDULED = 'createScheduled';
	const CMD_SWITCH_TO_LIST = 'switchToList';
	const CMD_SWITCH_TO_TILES = 'switchToTiles';
	const CMD_SHOW_CHAT_HISTORY = 'showChatHistory';
	const CMD_CHANGE_TILE_LIMIT = 'changeTileLimit';
    const CMD_REPUBLISH = 'republish';
	const CMD_OPENCAST_STUDIO = 'opencaststudio';
    const CMD_DOWNLOAD = 'download';

    /**
	 * @var xoctOpenCast
	 */
	protected $xoctOpenCast;
    /**
     * @var EventModals
     */
    protected $modals;


    /**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		$this->xoctOpenCast = $xoctOpenCast instanceof xoctOpenCast ? $xoctOpenCast : new xoctOpenCast();
	}


    /**
     * @throws DICException
     * @throws ilCtrlException
     * @throws xoctException
     */
    public function executeCommand()
    {
        $nextClass = self::dic()->ctrl()->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctPlayerGUI::class):
                $xoctEvent = xoctEvent::find(filter_input(INPUT_GET, self::IDENTIFIER));
                // check access
                if (!ilObjOpenCastAccess::hasReadAccessOnEvent($xoctEvent, xoctUser::getInstance(self::dic()->user()), $this->xoctOpenCast)) {
                    ilUtil::sendFailure($this->txt("msg_no_access"), true);
                    $this->cancel();
                }
                $xoctPlayerGUI = new xoctPlayerGUI($this->xoctOpenCast);
                self::dic()->ctrl()->forwardCommand($xoctPlayerGUI);
                break;
            default:
                $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }    }


    /**
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
        self::dic()->tabs()->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
        self::dic()->mainTemplate()->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.css');
        self::dic()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.js');	// init waiter
        self::dic()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getDirectory() . '/templates/default/reporting_modal.css');

        switch ($cmd) {
			case self::CMD_STANDARD:
				$this->prepareContent();
				break;
			default:
		}
		parent::performCommand($cmd);
	}


	/**
	 *
	 */
	protected function prepareContent() {
		xoctWaiterGUI::initJS();
		xoctWaiterGUI::addLinkOverlay('#rep_robj_xoct_event_clear_cache');
        self::dic()->mainTemplate()->addJavascript("./src/UI/templates/js/Modal/modal.js");
        self::dic()->mainTemplate()->addOnLoadCode('xoctEvent.init(\'' . json_encode([
                'msg_link_copied' => self::plugin()->translate('msg_link_copied'),
                'tooltip_copy_link' => self::plugin()->translate('tooltip_copy_link')
            ]) . '\');');


		// add "add" button
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
			$b = ilLinkButton::getInstance();
			$b->setCaption('rep_robj_xoct_event_add_new');
			$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD));
			$b->setPrimary(true);
			self::dic()->toolbar()->addButtonInstance($b);
		}

		// add "schedule" button
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && xoctConf::getConfig(xoctConf::F_CREATE_SCHEDULED_ALLOWED)) {
			$b = ilLinkButton::getInstance();
			$b->setCaption('rep_robj_xoct_event_schedule_new');
			$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_SCHEDULE));
			$b->setPrimary(true);
			self::dic()->toolbar()->addButtonInstance($b);
		}

		// add "Opencast Studio" button
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && xoctConf::getConfig(xoctConf::F_STUDIO_ALLOWED)) {
			$b = ilLinkButton::getInstance();
			$b->setCaption('rep_robj_xoct_event_opencast_studio');
			$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_OPENCAST_STUDIO));
			$b->setPrimary(true);
			self::dic()->toolbar()->addButtonInstance($b);
		}

		// add "clear cache" button
		if (xoctConf::getConfig(xoctConf::F_ACTIVATE_CACHE)) {
			$b = ilLinkButton::getInstance();
			$b->setId('rep_robj_xoct_event_clear_cache');
			$b->setCaption('rep_robj_xoct_event_clear_cache');
			$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_CLEAR_CACHE));
			self::dic()->toolbar()->addButtonInstance($b);
		}

		// add "clear clips" button (devmode)
		if (self::dic()->user()->getId() == 6 && ilObjOpenCast::DEV) {
			$b = ilLinkButton::getInstance();
			$b->setCaption('rep_robj_xoct_event_clear_clips_develop');
			$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, 'clearAllClips'));
			self::dic()->toolbar()->addButtonInstance($b);
		}

		// add "report date change" button
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
			$b = ilButton::getInstance();
			$b->setId('xoct_report_date_button');
			$b->setCaption('rep_robj_xoct_event_report_date_modification');
			$b->addCSSClass('hidden');

			self::dic()->toolbar()->addButtonInstance($b);
		}
	}


	/**
	 * asynchronous loading of tableGUI
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	protected function index() {
		ilChangeEvent::_recordReadEvent(
			$this->xoctOpenCast->getILIASObject()->getType(),
			$this->xoctOpenCast->getILIASObject()->getRefId(),
			$this->xoctOpenCast->getObjId(),
			self::dic()->user()->getId()
		);

		switch (xoctUserSettings::getViewTypeForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'))) {
			case xoctUserSettings::VIEW_TYPE_LIST:
				$html = $this->indexList();
				break;
			case xoctUserSettings::VIEW_TYPE_TILES:
				$html = $this->indexTiles();
				break;
			default:
				throw new xoctException(xoctException::INTERNAL_ERROR, 'Invalid view type ' .
					xoctUserSettings::getViewTypeForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id')) .
					' for user with id ' . self::dic()->user()->getId());
		}

		self::dic()->mainTemplate()->setContent($this->getIntroTextHTML() . $html);
	}

	/**
	 * @return string
	 * @throws DICException
	 */
	protected function indexList() {
		$this->initViewSwitcherHTML('list');

		if (isset($_GET[xoctEventTableGUI::getGeneratedPrefix($this->xoctOpenCast) . '_xpt']) || !empty($_POST)) {
			// you're here when exporting or changing selected columns
			$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast);
			if ($xoctEventTableGUI->hasScheduledEvents()) {
				self::dic()->mainTemplate()->addOnLoadCode("$('#xoct_report_date_button').removeClass('hidden');");
			}
			return $xoctEventTableGUI->getHTML();
		}

		self::dic()->mainTemplate()->addJavascript("./Services/Table/js/ServiceTable.js");
		$this->loadAjaxCodeForList();	// the tableGUI is loaded asynchronously
		return '<div id="xoct_table_placeholder"></div>';
	}

	/**
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	protected function indexTiles() {
		$this->initViewSwitcherHTML('tiles');

		$this->loadAjaxCodeForTiles();	// the tilesGUI is loaded asynchronously
		return '<div id="xoct_tiles_placeholder"></div>';
	}

	/**
	 * @param $active
	 * @return string
	 * @throws DICException
	 */
	protected function initViewSwitcherHTML($active) {
		if (xoct::isIlias54() && $this->xoctOpenCast->isViewChangeable()) {
			$f = self::dic()->ui()->factory();
			$renderer = self::dic()->ui()->renderer();

			$actions = [
				self::plugin()->translate('list') => self::dic()->ctrl()->getLinkTarget($this, self::CMD_SWITCH_TO_LIST),
				self::plugin()->translate('tiles') => self::dic()->ctrl()->getLinkTarget($this, self::CMD_SWITCH_TO_TILES),
			];

			$aria_label = self::plugin()->translate('info_view_switcher');
			$view_control = $f->viewControl()->mode($actions, $aria_label)->withActive(self::plugin()->translate($active));
			self::dic()->toolbar()->addText($renderer->render($view_control));
		}
	}

	/**
	 *
	 */
	protected function switchToTiles() {
		xoctUserSettings::changeViewType(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), xoctUserSettings::VIEW_TYPE_TILES);
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}

	/**
	 *
	 */
	protected function switchToList() {
		xoctUserSettings::changeViewType(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), xoctUserSettings::VIEW_TYPE_LIST);
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}

	/**
	 *	called by 'tiles per page' selector
	 */
	protected function changeTileLimit() {
		$tile_limit = filter_input(INPUT_POST, 'tiles_per_page');
		if (in_array($tile_limit, [4, 8, 12, 16])) {
			xoctUserSettings::changeTileLimit(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), $tile_limit);
		}
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}

	/**
	 *
	 */
	protected function loadAjaxCodeForList() {
		foreach ($_GET as $para => $value) {
			self::dic()->ctrl()->setParameter($this, $para, $value);
		}

		$ajax_link = self::dic()->ctrl()->getLinkTarget($this, 'asyncGetTableGUI', "", true);

		// hacky stuff to allow asynchronous rendering of tableGUI
		$table_id = xoctEventTableGUI::getGeneratedPrefix($this->xoctOpenCast);
		$user_id = self::dic()->user()->getId();
		$tab_prop = new ilTablePropertiesStorage();
		if ($tab_prop->getProperty($table_id, $user_id, 'filter')) {
			$activate_filter_commmand = "ilShowTableFilter('tfil_$table_id', './ilias.php?baseClass=ilTablePropertiesStorage&table_id=$table_id&cmd=showFilter&user_id=$user_id');";
		}

		$ajax = "$.ajax({
				    url: '{$ajax_link}',
				    dataType: 'html',
				    success: function(data){
				        xoctWaiter.hide();
				        $('div#xoct_table_placeholder').replaceWith($(data));
				        $activate_filter_commmand
				    }
				});";
		self::dic()->mainTemplate()->addOnLoadCode('xoctWaiter.show();');
		self::dic()->mainTemplate()->addOnLoadCode($ajax);
	}

	/**
	 *
	 */
	protected function loadAjaxCodeForTiles() {
		foreach ($_GET as $para => $value) {
			self::dic()->ctrl()->setParameter($this, $para, $value);
		}
		$ajax_link = self::dic()->ctrl()->getLinkTarget($this, 'asyncGetTilesGUI', "", true);
		$ajax = "$.ajax({
				    url: '{$ajax_link}',
				    dataType: 'html',
				    success: function(data){
				        xoctWaiter.hide();
				        $('div#xoct_tiles_placeholder').replaceWith($(data));
				    }
				});";
		self::dic()->mainTemplate()->addOnLoadCode('xoctWaiter.show();');
		self::dic()->mainTemplate()->addOnLoadCode($ajax);}

	/**
	 * ajax call
	 */
	public function asyncGetTableGUI() {
        $modals_html = $this->getModalsHTML();
        $xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast);
        $html = $xoctEventTableGUI->getHTML();
        if ($xoctEventTableGUI->hasScheduledEvents()) {
            $signal = $this->getModals()->getReportDateModal()->getShowSignal()->getId();
            $html .= "<script type='text/javascript'>
                        $('#xoct_report_date_button').removeClass('hidden');
                        $('#xoct_report_date_button').on('click', function(){
                            $(this).trigger('$signal',
							{
								'id' : '$signal', 'event' : 'click',
								'triggerer' : $(this),
								'options' : JSON.parse('[]')
							});
                        });
                    </script>";
        }
        echo $html . $modals_html;
        exit();
	}


	/**
	 * ajax call
	 */
	public function asyncGetTilesGUI() {
		$xoctEventTileGUI = new xoctEventTileGUI($this, $this->xoctOpenCast);
		$html = $this->getModalsHTML();
        $html .= $xoctEventTileGUI->getHTML();
        if ($xoctEventTileGUI->hasScheduledEvents()) {
            $signal = $this->getModals()->getReportDateModal()->getShowSignal()->getId();
            $html .= "<script type='text/javascript'>
                        $('#xoct_report_date_button').removeClass('hidden');
                        $('#xoct_report_date_button').on('click', function(){
                            $(this).trigger('$signal',
							{
								'id' : '$signal', 'event' : 'click',
								'triggerer' : $(this),
								'options' : JSON.parse('[]')
							});
                        });
                    </script>";
        }
        echo $html;
        exit();
	}


	/**
	 *
	 */
	protected function applyFilter() {
		$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast, false);
		$xoctEventTableGUI->resetOffset(true);
		$xoctEventTableGUI->writeFilterToSession();
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}


	/**
	 *
	 */
	protected function resetFilter() {
		//		xoctEventTableGUI::setDefaultRowValue($this->xoctOpenCast);
		$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast, false);
		$xoctEventTableGUI->resetOffset();
		$xoctEventTableGUI->resetFilter();
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}


	/**
	 *
	 */
	protected function add() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
		}
		$xoctEventFormGUI = new EventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function create() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEventFormGUI = new EventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);

		$xoctAclStandardSets = new xoctAclStandardSets($xoctUser->getOwnerRoleName() ? array($xoctUser->getOwnerRoleName(), $xoctUser->getUserRoleName()) : array());
		$xoctEventFormGUI->getObject()->setAcl($xoctAclStandardSets->getAcls());

		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_created'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
		}
		$xoctEventFormGUI->setValuesByPost();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function uploadChunks() {
		$plupload = new Plupload();
		$plupload->handleUpload();
	}


	/**
	 *
	 */
	protected function schedule() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
		}
		$xoctEventFormGUI = new EventFormGUI($this, new xoctEvent(), $this->xoctOpenCast, true);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function createScheduled() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEventFormGUI = new EventFormGUI($this, new xoctEvent(), $this->xoctOpenCast, true);

		$xoctAclStandardSets = new xoctAclStandardSets($xoctUser->getOwnerRoleName() ? array($xoctUser->getOwnerRoleName(), $xoctUser->getUserRoleName()) : array());
		$xoctEventFormGUI->getObject()->setAcl($xoctAclStandardSets->getAcls());

		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_scheduled'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
		}
		$xoctEventFormGUI->setValuesByPost();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}

    /**
     * @throws DICException
     * @throws ilDateTimeException
     * @throws xoctException
     */
	protected function edit() {
		/**
		 * @var xoctEvent $xoctEvent
		 */
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctUser = xoctUser::getInstance(self::dic()->user());

		// check access
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}

		$xoctEventFormGUI = new EventFormGUI($this, $xoctEvent, $this->xoctOpenCast);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 * 
	 */
	public function opencaststudio(){
		$xoctSeries =  $this->xoctOpenCast->getSeriesIdentifier();
		$base = rtrim(xoctConf::getConfig(xoctConf::F_API_BASE), "/");
		$base = str_replace('/api', '', $base);
		$studio_link = $base . '/studio' . '?upload.seriesId=' . $xoctSeries;
		header('Location:' . $studio_link);
	}
		

	/**
	 *
	 */
	public function cut() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);

		// check access
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}

		// add user to ilias producers
		try {
			$ilias_producers = Group::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
			$sleep = $ilias_producers->addMember($xoctUser);
		} catch (xoctException $e) {
			$sleep = false;
		}

		// add user to series producers
		/** @var xoctSeries $xoctSeries */
		$xoctSeries = xoctSeries::find($xoctEvent->getSeriesIdentifier());
		if ($xoctSeries->addProducer($xoctUser)) {
            $sleep = true;
        }

		if ($sleep) {
			sleep(3);
		}

		// redirect
		$cutting_link = $xoctEvent->publications()->getCuttingLink();
		header('Location: ' . $cutting_link);
	}

    /**
     * @throws xoctException
     */
	public function download()
    {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_STRING);
        $publication_id = filter_input(INPUT_GET, 'pub_id', FILTER_SANITIZE_STRING);
        $event = xoctEvent::find($event_id);
        $download_publications = $event->publications()->getDownloadPublications();
        if ($publication_id) {
            $publication = array_filter($download_publications, function($publication) use ($publication_id) {
                return $publication->getId() == $publication_id;
            });
            $publication = array_shift($publication);
        } else {
           $publication = array_shift($download_publications);
        }
        $url = $publication->getUrl();
        $extension = pathinfo($url)['extension'];
        $url = xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;

        // get filesize
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        // deliver file
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $publication->getMediatype());
        header('Content-Disposition: attachment; filename="' . $event->getTitle() . '.' . $extension . '"');
        header('Content-Length: ' . $size);
        readfile($url);
        exit;
    }


    /**
     *
     * @throws xoctException
     */
	public function annotate() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);

		// check access
		if (ilObjOpenCastAccess::hasPermission('edit_videos') || ilObjOpenCastAccess::hasWriteAccess()) {
            // add user to ilias producers
            try {
                $ilias_producers = Group::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
                $sleep = $ilias_producers->addMember($xoctUser);
            } catch (xoctException $e) {
                $sleep = false;
            }

            // add user to series producers
            /** @var xoctSeries $xoctSeries */
            $xoctSeries = xoctSeries::find($xoctEvent->getSeriesIdentifier());
            if ($xoctSeries->addProducer($xoctUser)) {
                $sleep = true;
            }

            if ($sleep) {
                sleep(3);
            }
        }


		// redirect
		$annotation_link = $xoctEvent->publications()->getAnnotationLink(
		    filter_input(INPUT_GET, 'ref_id', FILTER_SANITIZE_NUMBER_INT)
        );

		header('Location: ' . $annotation_link);
	}


	/**
	 *
	 */
	public function setOnline() {
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctEvent->getXoctEventAdditions()->setIsOnline(true);
		$xoctEvent->getXoctEventAdditions()->update();
		$this->cancel();
	}


	/**
	 *
	 */
	public function setOffline() {
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctEvent->getXoctEventAdditions()->setIsOnline(false);
		$xoctEvent->getXoctEventAdditions()->update();
		$this->cancel();
	}


	/**
	 *
	 */
	protected function update() {
		/**
		 * @var xoctEvent $xoctEvent
		 */
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}

		$xoctEventFormGUI = new EventFormGUI($this, xoctEvent::find($_GET[self::IDENTIFIER]), $this->xoctOpenCast);
		$xoctEventFormGUI->setValuesByPost();

		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
		}
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


    /**
     * @throws DICException
     * @throws xoctException
     */
	protected function republish()
    {
        $post_body = self::dic()->http()->request()->getParsedBody();
        if (isset($post_body['workflow_id']) && is_string($post_body['workflow_id'])
            && isset($post_body['republish_event_id']) && is_string($post_body['republish_event_id'])
        ) {
            $workflow_id = strip_tags($post_body['workflow_id']);
            $event_id = strip_tags($post_body['republish_event_id']);
            $workflow = (new WorkflowRepository())->getByWorkflowId($workflow_id);
            if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, new xoctEvent($event_id))
                || is_null($workflow)) {
                ilUtil::sendFailure($this->txt('msg_no_access'), true);
                $this->cancel();
            }
            $request = [
                'event_identifier' => $event_id,
                'workflow_definition_identifier' => $workflow_id,
            ];
            $params = [];
            foreach (explode(',', $workflow->getParameters()) as $param) {
                $params[$param] = 'true';
            }
            if (!empty($params)) {
                $request['configuration'] = json_encode($params);
            }
            xoctRequest::root()->workflows()->post($request);
            ilUtil::sendSuccess($this->txt('msg_republish_started'), true);
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        } else {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
    }

	/**
	 *
	 */
	protected function removeInvitations() {
		foreach (xoctInvitation::get() as $xoctInvitation) {
			$xoctInvitation->delete();
		}
		ilUtil::sendSuccess($this->txt('msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}


	/**
	 *
	 */
	protected function clearAllClips() {
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$a_data = (new EventRepository(self::dic()->dic()))->getFiltered($filter);
		/**
		 * @var $xoctEvent      xoctEvent
		 * @var $xoctInvitation xoctInvitation
		 * @var $xoctGroup      xoctIVTGroup
		 */
		foreach ($a_data as $i => $d) {
			$xoctEvent = xoctEvent::find($d['identifier']);
			$xoctEvent->setTitle('Clip ' . $i);
			$xoctEvent->setDescription('Subtitle ' . $i);
			$xoctEvent->setPresenter('Presenter ' . $i);
			$xoctEvent->setLocation('Station ' . $i);
			$xoctEvent->setCreated(new DateTime());
			$xoctEvent->removeOwner();
			$xoctEvent->removeAllOwnerAcls();
			$xoctEvent->update();
			foreach (xoctInvitation::where(array( 'event_identifier' => $xoctEvent->getIdentifier() ))->get() as $xoctInvitation) {
				$xoctInvitation->delete();
			}
		}
		foreach (xoctIVTGroup::where(array( 'serie_id' => $this->xoctOpenCast->getObjId() ))->get() as $xoctGroup) {
			$xoctGroup->delete();
		}

		$this->cancel();
	}


	/**
	 *
	 */
	protected function resetPermissions() {
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$a_data = (new EventRepository(self::dic()->dic()))->getFiltered($filter);
		/**
		 * @var $xoctEvent      xoctEvent
		 * @var $xoctInvitation xoctInvitation
		 * @var $xoctGroup      xoctIVTGroup
		 */
		$errors = 'Folgende Clips konnten nicht upgedatet werden: ';
		foreach ($a_data as $i => $d) {
			$xoctEvent = xoctEvent::find($d['identifier']);
			try {
				$xoctEvent->update();
			} catch (xoctException $e) {
				$errors .= $xoctEvent->getTitle() . '; ';
			}
		}
		$this->cancel();
	}


	/**
	 *
	 */
	protected function confirmDelete() {
		/**
		 * @var xoctEvent $xoctEvent
		 */
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}
		$ilConfirmationGUI = new ilConfirmationGUI();
		$ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this));
        if (count($xoctEvent->publications()->getPublications()) && xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH)) {
            $header_text = $this->txt('unpublish_confirm');
            $action_text = 'unpublish';
        } else {
            $header_text = $this->xoctOpenCast->getDuplicatesOnSystem() ? $this->txt('delete_confirm_w_duplicates') : $this->txt('delete_confirm');
            $action_text = 'delete';
        }
		$ilConfirmationGUI->setHeaderText($header_text);
		$ilConfirmationGUI->setCancel($this->txt('cancel'), self::CMD_CANCEL);
		$ilConfirmationGUI->setConfirm($this->txt($action_text), self::CMD_DELETE);
		$ilConfirmationGUI->addItem(self::IDENTIFIER, $xoctEvent->getIdentifier(), $xoctEvent->getTitle());
		self::dic()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
	}


	/**
	 * @throws DICException
	 * @throws xoctException
	 */
	protected function delete() {
		$xoctEvent = xoctEvent::find($_POST[self::IDENTIFIER]);
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}
        if (count($xoctEvent->publications()->getPublications()) && xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH)) {
            try {
                $xoctEvent->unpublish();
                ilUtil::sendSuccess($this->txt('msg_unpublish_started'), true);
            } catch (xoctException $e) {
                if ($e->getCode() == 409) {
                    ilUtil::sendInfo($this->txt('msg_currently_unpublishing'), true);
                } else {
                    throw $e;
                }
            }
        } else {
            $xoctEvent->delete();
            ilUtil::sendSuccess($this->txt('msg_deleted'), true);
        }
		$this->cancel();
	}


	/**
	 *
	 */
	protected function view() {
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		echo '<pre>' . print_r($xoctEvent, 1) . '</pre>';
		exit;
	}


	/**
	 *
	 */
	protected function search() {
		/**
		 * @var $event xoctEvent
		 */
		$form = new ilPropertyFormGUI();
		$form->setFormAction(self::dic()->ctrl()->getFormAction($this));
		$form->addCommandButton('import', 'Import');
		$self = new ilSelectInputGUI('import_identifier', 'import_identifier');

		$request = xoctRequest::root()->events()->parameter('limit', 1000);
		$data = json_decode($request->get());
		$ids = array();
		foreach ($data as $d) {
			$event = xoctEvent::find($d->identifier);
			$ids[$event->getIdentifier()] = $event->getTitle() . ' (...' . substr($event->getIdentifier(), - 4, 4) . ')';
		}
		array_multisort($ids);

		$self->setOptions($ids);
		$form->addItem($self);
		self::dic()->mainTemplate()->setContent($form->getHTML());
	}


	/**
	 *
	 */
	protected function import() {
		/**
		 * @var $event xoctEvent
		 */
		$event = xoctEvent::find($_POST['import_identifier']);
		$html = 'Series before set: ' . $event->getSeriesIdentifier() . '<br>';
		$event->setSeriesIdentifier($this->xoctOpenCast->getSeriesIdentifier());
		$html .= 'Series after set: ' . $event->getSeriesIdentifier() . '<br>';
		$event->updateSeries();
		$html .= 'Series after update: ' . $event->getSeriesIdentifier() . '<br>';
		$event = new xoctEvent($_POST['import_identifier']);
		$html .= 'Series after new read: ' . $event->getSeriesIdentifier() . '<br>';
		self::dic()->mainTemplate()->setContent($html);
	}


	/**
	 *
	 */
	protected function clearCache() {
		xoctCacheFactory::getInstance()->flush();
		$this->xoctOpenCast->getSeriesIdentifier();
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}

	/**
	 * @return string
	 */
	protected function getModalsHTML() {
        $modals_html = '';
        foreach ($this->getModals()->getAllComponents() as $modal) {
            $modals_html .= self::dic()->ui()->renderer()->renderAsync($modal);
	    }

		return $modals_html;
	}


	/**
	 *
	 */
	protected function reportDate() {
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
            $message = $this->getDateReportMessage($_POST['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «geplante Termine anpassen»';
            $report = new xoctReport();
            $report->setType(xoctReport::TYPE_DATE)
                ->setUserId(self::dic()->user()->getId())
                ->setSubject($subject)
                ->setMessage($message)
                ->create();
        }
		ilUtil::sendSuccess(self::plugin()->translate('msg_date_report_sent'), true);
		self::dic()->ctrl()->redirect($this);
	}


	/**
	 *
	 */
	protected function reportQuality() {
		$event = new xoctEvent($_POST['event_id']);
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $event)) {
            $message = $this->getQualityReportMessage($event, $_POST['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «Qualitätsprobleme»';

            $report = new xoctReport();
            $report->setType(xoctReport::TYPE_QUALITY)
                ->setUserId(self::dic()->user()->getId())
                ->setSubject($subject)
                ->setMessage($message)
                ->create();
		}
		ilUtil::sendSuccess(self::plugin()->translate('msg_quality_report_sent'), true);
		self::dic()->ctrl()->redirect($this);
	}

    /**
     * @param xoctEvent $event
     * @param $message
     * @return string
     */
    protected function getQualityReportMessage(xoctEvent $event, $message) {
        $link = ilLink::_getStaticLink($_GET['ref_id'], ilOpenCastPlugin::PLUGIN_ID,
            true);
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($_GET['ref_id']);
        $crs_grp_role = ilObjOpenCast::_getCourseOrGroupRole();
	    $mail_body =
            "Dies ist eine automatische Benachrichtigung des ILIAS Opencast Plugins <br><br>"
            . "Es gab eine neue Meldung im Bereich «Qualitätsprobleme melden». <br><br>"
            . "<b>Benutzer/in:</b> " . self::dic()->user()->getLogin() . ", " . self::dic()->user()->getEmail() . " <br>"
            . "<b>Rolle im ILIAS-Kurs:</b> $crs_grp_role <br><br>"
            . "<b>Opencast Serie in ILIAS:</b> $link<br>"
            . "<b>Titel Opencast Event:</b> {$event->getTitle()}<br>"
            . "<b>ID Opencast Event:</b> {$event->getIdentifier()}<br>"
            . "<b>Titel Opencast Serie:</b> {$series->getILIASObject()->getTitle()}<br>"
            . "<b>ID Opencast Serie:</b> {$series->getSeriesIdentifier()}<br><br>"
            . "<b>Nachrichtentext:</b> <br>"
            . "<hr>"
            . nl2br($message) . "<br>"
            . "<hr>";
	    return $mail_body;
    }

    /**
     * @param $message
     * @return string
     */
    protected function getDateReportMessage($message) {
        $link = ilLink::_getStaticLink($_GET['ref_id'], ilOpenCastPlugin::PLUGIN_ID,
            true);
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($_GET['ref_id']);
        $mail_body =
            "Dies ist eine automatische Benachrichtigung des ILIAS Opencast Plugins <br><br>"
            . "Es gab eine neue Meldung im Bereich «geplante Termine anpassen». <br><br>"
            . "<b>Benutzer/in:</b> " . self::dic()->user()->getLogin() . ", " . self::dic()->user()->getEmail() . " <br><br>"
            . "<b>Opencast Serie in ILIAS:</b> $link<br>"
            . "<b>Titel Opencast Serie:</b> {$series->getILIASObject()->getTitle()}<br>"
            . "<b>ID Opencast Serie:</b> {$series->getSeriesIdentifier()}<br><br>"
            . "<b>Nachrichtentext:</b> <br>"
            . "<hr>"
            . nl2br($message) . "<br>"
            . "<hr>";
        return $mail_body;
    }


	/**
	 * @param $key
	 *
	 * @return string
	 * @throws DICException
	 */
	public function txt($key) {
		return self::plugin()->translate('event_' . $key);
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->xoctOpenCast->getObjId();
	}


    /**
     * @return EventModals
     * @throws DICException
     * @throws ilTemplateException
     */
	public function getModals() : EventModals
    {
    	if (is_null($this->modals)) {
            $modals = new EventModals($this, self::plugin()->getPluginObject(), self::dic()->dic(), new WorkflowRepository());
            $modals->initRepublish();
            $modals->initReportDate();
            $modals->initReportQuality();
            $this->modals = $modals;
            xoctEventRenderer::initModals($modals);
        }
    	return $this->modals;
    }

	/**
	 * @return string
	 * @throws ilTemplateException
	 */
	protected function getIntroTextHTML() {
		$intro_text = '';
		if ($this->xoctOpenCast->getIntroductionText()) {
			$intro = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/tpl.intro.html', '', true, true);
			$intro->setVariable('INTRO', nl2br($this->xoctOpenCast->getIntroductionText()));
			$intro_text = $intro->get();
		}
		return $intro_text;
	}

	/**
	 *
	 */
	protected function showChatHistory() {
		$event_id = filter_input(INPUT_GET, 'event_id');
		$chatroom = ChatroomAR::findBy($event_id, $this->getObjId());
		if ($chatroom) {
			$ChatHistoryGUI = new ChatHistoryGUI($chatroom->getId());
			echo $ChatHistoryGUI->render();
			exit;
		}
	}
}
