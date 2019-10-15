<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Chat\GUI\ChatGUI;
use srag\Plugins\Opencast\Chat\GUI\ChatHistoryGUI;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Chat\Model\MessageAR;
use srag\Plugins\Opencast\Chat\Model\TokenAR;

/**
 * Class xoctEventGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI {

	const IDENTIFIER = 'eid';
	const CMD_STANDARD = 'index';
	const CMD_SHOW_CONTENT = 'showContent';
	const CMD_CLEAR_CACHE = 'clearCache';
	const CMD_EDIT_OWNER = 'editOwner';
	const CMD_UPDATE_OWNER = 'updateOwner';
	const CMD_UPLOAD_CHUNKS = 'uploadChunks';
	const CMD_SET_ONLINE = 'setOnline';
	const CMD_SET_OFFLINE = 'setOffline';
	const CMD_CUT = 'cut';
	const CMD_ANNOTATE = 'annotate';
	const CMD_REPORT_DATE = 'reportDate';
	const CMD_REPORT_QUALITY = 'reportQuality';
	const CMD_SCHEDULE = 'schedule';
	const CMD_CREATE_SCHEDULED = 'createScheduled';
	const CMD_DELIVER_VIDEO = 'deliverVideo';
	const CMD_STREAM_VIDEO = 'streamVideo';
	const CMD_SWITCH_TO_LIST = 'switchToList';
	const CMD_SWITCH_TO_TILES = 'switchToTiles';
	const CMD_SHOW_CHAT_HISTORY = 'showChatHistory';
	const CMD_CHANGE_TILE_LIMIT = 'changeTileLimit';

	const ROLE_MASTER = "presenter";
	const ROLE_SLAVE = "presentation";

	/**
	 * @var xoctOpenCast
	 */
	protected $xoctOpenCast;


	/**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		$this->xoctOpenCast = $xoctOpenCast instanceof xoctOpenCast ? $xoctOpenCast : new xoctOpenCast();
		self::dic()->tabs()->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
		self::dic()->mainTemplate()->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.css');
		self::dic()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.js');
	}


	/**
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
		switch ($cmd) {
			case self::CMD_STANDARD:
			case self::CMD_SHOW_CONTENT:
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
		// init waiter
		xoctWaiterGUI::initJS();
		xoctWaiterGUI::addLinkOverlay('#rep_robj_xoct_event_clear_cache');

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
			$b->setOnClick("$('#xoct_report_date_modal').modal('show');");
			$b->addCSSClass('hidden');

			self::dic()->toolbar()->addButtonInstance($b);
		}
	}


	/**
	 * same cmd as standard command (index()), except it's synchronous
	 */
	protected function showContent() {
		$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast, true);
        if ($xoctEventTableGUI->hasScheduledEvents()) {
            self::dic()->mainTemplate()->addOnLoadCode("$('#xoct_report_date_button').removeClass('hidden');");
        }
		self::dic()->mainTemplate()->setContent($this->getIntroTextHTML() . $xoctEventTableGUI->getHTML() . $this->getModalsHTML());
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

		self::dic()->mainTemplate()->setContent($this->getIntroTextHTML() . $html . $this->getModalsHTML());
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
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	protected function showContentTiles() {
		$this->initViewSwitcherHTML('tiles');

		$xoctEventTileGUI = new xoctEventTileGUI($this, $this->xoctOpenCast);

		return $xoctEventTileGUI->getHTML();
		self::dic()->mainTemplate()->setContent($xoctEventTileGUI->getHTML());
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
		$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast);
        $html = $xoctEventTableGUI->getHTML();
        if ($xoctEventTableGUI->hasScheduledEvents()) {
            $html .= "<script type='text/javascript'>$('#xoct_report_date_button').removeClass('hidden');</script>";
        }
        echo $html;
        exit();
	}


	/**
	 * ajax call
	 */
	public function asyncGetTilesGUI() {
		$xoctEventTileGUI = new xoctEventTileGUI($this, $this->xoctOpenCast);
        $html = $xoctEventTileGUI->getHTML();
        if ($xoctEventTileGUI->hasScheduledEvents()) {
            $html .= "<script type='text/javascript'>$('#xoct_report_date_button').removeClass('hidden');</script>";
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
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function create() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);

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
		$xoctPlupload = new xoctPlupload();
		$xoctPlupload->handleUpload();
	}


	/**
	 *
	 */
	protected function schedule() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
		}
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast, true);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function createScheduled() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast, true);

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
	 *
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

		$xoctEventFormGUI = new xoctEventFormGUI($this, $xoctEvent, $this->xoctOpenCast);
		$xoctEventFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
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
			$ilias_producers = xoctGroup::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
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
		$cutting_link = $xoctEvent->getCuttingLink();
		header('Location: ' . $cutting_link);
	}

	/**
	 *
	 */
	public function annotate() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);

		// check access
		if (ilObjOpenCastAccess::hasPermission('edit_videos') || ilObjOpenCastAccess::hasWriteAccess()) {
            // add user to ilias producers
            try {
                $ilias_producers = xoctGroup::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
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
		$cutting_link = $xoctEvent->getAnnotationLink();
		header('Location: ' . $cutting_link);
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
	public function streamVideo() {
		$xoctEvent = xoctEvent::find(filter_input(INPUT_GET, self::IDENTIFIER));

		// check access
		if (!ilObjOpenCastAccess::hasReadAccessOnEvent($xoctEvent, xoctUser::getInstance(self::dic()->user()), $this->xoctOpenCast)) {
			ilUtil::sendFailure($this->txt("msg_no_access"), true);
			$this->cancel();
		}

        $tpl = self::plugin()->getPluginObject()->getTemplate("paella_player.html", true, true);

        $tpl->setVariable("TITLE", $xoctEvent->getTitle());
        $tpl->setVariable("PAELLA_PLAYER_FOLDER", self::plugin()->getPluginObject()->getDirectory() . "/node_modules/paellaplayer/build/player");
        $tpl->setVariable("PAELLA_CONFIG_FILE", self::plugin()->getPluginObject()->getDirectory() . "/js/paella_player/config.json");

        try {
            $data = $xoctEvent->isLiveEvent() ? $this->getLiveStreamingData($xoctEvent) : $this->getStreamingData($xoctEvent);
        } catch (xoctException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        }
        $tpl->setVariable("DATA", json_encode($data));
        $tpl->setVariable('IS_LIVE_STREAM', $xoctEvent->isLiveEvent() ? 'true' : 'false');

        $start = 0;
        $end = 0;
        if ($xoctEvent->isLiveEvent()) {
            $start = $xoctEvent->getScheduling()->getStart()->getTimestamp();
            $end = $xoctEvent->getScheduling()->getEnd()->getTimestamp();
            $tpl->setVariable('LIVE_WAITING_TEXT', self::plugin()->translate('live_waiting_text', 'event', [date('H:i', $start)]));
            $tpl->setVariable('LIVE_INTERRUPTED_TEXT', self::plugin()->translate('live_interrupted_text', 'event'));
            $tpl->setVariable('LIVE_OVER_TEXT', self::plugin()->translate('live_over_text', 'event'));

            $tpl->setVariable('CHECK_SCRIPT_HLS', self::plugin()->directory() . '/src/Util/check_hls_status.php'); // used for live streams
        }

        $tpl->setVariable('EVENT_START', $start);
        $tpl->setVariable('EVENT_END', $end);

		if ($xoctEvent->getProcessingState() == xoctEvent::STATE_LIVE_SCHEDULED) {
            $tpl->setVariable('INLINE_JS', 'loadPlayer();');

        } else {
		    $tpl->setVariable('INLINE_JS', 'loadPlayer();');
        }


        $ChatroomAR = ChatroomAR::findBy($xoctEvent->getIdentifier(), $this->xoctOpenCast->getObjId());
        if (!filter_input(INPUT_GET, 'force_no_chat') && xoctConf::getConfig(xoctConf::F_ENABLE_CHAT) && $this->xoctOpenCast->isChatActive()) {
            if ($xoctEvent->isLiveEvent()) {
                $tpl->setVariable("STYLE_SHEET_LOCATION", ILIAS_HTTP_PATH . '/' . self::plugin()->getPluginObject()->getDirectory() . "/templates/default/player_w_chat.css");
                $ChatroomAR = ChatroomAR::findOrCreate($xoctEvent->getIdentifier(), $this->getObjId());
                $TokenAR = TokenAR::getNewFrom($ChatroomAR->getId(), self::dic()->user()->getId(), self::dic()->user()->getPublicName());
                $ChatGUI = new ChatGUI($TokenAR);
                $tpl->setVariable('CHAT', $ChatGUI->render(true));
            } elseif ($ChatroomAR && MessageAR::where(["chat_room_id" => $ChatroomAR->getId()])->hasSets()) {
                $tpl->setVariable("STYLE_SHEET_LOCATION", ILIAS_HTTP_PATH . '/' . self::plugin()->getPluginObject()->getDirectory() . "/templates/default/player_w_chat.css");
                $ChatHistoryGUI = new ChatHistoryGUI($ChatroomAR->getId());
                $tpl->setVariable('CHAT', $ChatHistoryGUI->render(true));
            }
        }

		echo $tpl->get();

		exit();
	}

    /**
     *
     */
    protected function deliverVideo() {
        $event_id = $_GET['event_id'];
        $mid = $_GET['mid'];
        $xoctEvent = xoctEvent::find($event_id);
        $media = $xoctEvent->getFirstPublicationMetadataForUsage(xoctPublicationUsage::getUsage(xoctPublicationUsage::USAGE_PLAYER))->getMedia();
        foreach ($media as $medium) {
            if ($medium->getId() == $mid) {
                $url = $medium->getUrl();
                break;
            }
        }
        if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
            $url = xoctSecureLink::sign($url);
        }
//		$ctype= 'video/mp4';
//		header('Content-Type: ' . $ctype);
//		$handle = fopen($url, "rb");
//		fpassthru($handle);
//		$contents = fread($handle, filesize(()));
//		fclose($handle);
//		echo $contents;
		header("Location: " . $url);
		exit;

        // this request fetches the filesize. Better cache filesize to reduce loading time
        ini_set('max_execution_time', 0);
        $useragent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36";
        $v = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 222222);
        curl_setopt($ch, CURLOPT_URL, $v);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $info = curl_exec($ch);
        $size2 = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        header("Content-Type: video/mp4");


        $filesize = $size2;
        $offset = 0;
        $length = $filesize;
        if (isset($_SERVER['HTTP_RANGE'])) {
            $partialContent = "true";
            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
            $offset = intval($matches[1]);
            $length = $size2 - $offset - 1;
        } else {
            $partialContent = "false";
        }
        if ($partialContent == "true") {
            header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes '.$offset.
                '-'.($offset + $length).
                '/'.$filesize);
        } else {
            header('Accept-Ranges: bytes');
        }
        header("Content-length: ".$size2);


        $ch = curl_init();
        if (isset($_SERVER['HTTP_RANGE'])) {
            // if the HTTP_RANGE header is set we're dealing with partial content
            $partialContent = true;
            // find the requested range
            // this might be too simplistic, apparently the client can request
            // multiple ranges, which can become pretty complex, so ignore it for now
            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
            $offset = intval($matches[1]);
            $length = $filesize - $offset - 1;
            $headers = array(
                'Range: bytes='.$offset.
                '-'.($offset + $length).
                ''
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 222222);
        curl_setopt($ch, CURLOPT_URL, $v);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_exec($ch);
        exit;
//		echo $out;
    }


	/**
	 *
	 */
	protected function saveAndStay() {
		/**
		 * @var xoctEvent $xoctEvent
		 */
		$xoctEvent = xoctEvent::find($_GET[self::IDENTIFIER]);
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $xoctEvent, $xoctUser)) {
			ilUtil::sendFailure($this->txt('msg_no_access'), true);
			$this->cancel();
		}

		$xoctEventFormGUI = new xoctEventFormGUI($this, xoctEvent::find($_GET[self::IDENTIFIER]), $this->xoctOpenCast);
		$xoctEventFormGUI->setValuesByPost();

		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_EDIT);
		}
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
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

		$xoctEventFormGUI = new xoctEventFormGUI($this, xoctEvent::find($_GET[self::IDENTIFIER]), $this->xoctOpenCast);
		$xoctEventFormGUI->setValuesByPost();

		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
		}
		self::dic()->mainTemplate()->setContent($xoctEventFormGUI->getHTML());
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
		$a_data = xoctEvent::getFiltered($filter, NULL, NULL);
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
		$a_data = xoctEvent::getFiltered($filter, NULL, NULL);
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
        if (count($xoctEvent->getPublications()) && xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH)) {
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
        if (count($xoctEvent->getPublications()) && xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH)) {
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
	protected function listAll() {
		/**
		 * @var $event xoctEvent
		 */
		$request = xoctRequest::root()->events()->parameter('limit', 1000);
		$content = '';
		foreach (json_decode($request->get()) as $d) {
			$event = xoctEvent::find($d->identifier);
			$content .= '<pre>' . print_r($event->__toStdClass(), 1) . '</pre>';
		}
		self::dic()->mainTemplate()->setContent($content);
	}


	/**
	 *
	 */
	protected function clearCache() {
		xoctCacheFactory::getInstance()->flush();
		$this->xoctOpenCast->getSeriesIdentifier();
		self::dic()->ctrl()->redirect($this, self::CMD_SHOW_CONTENT);
	}

	/**
	 * @return string
	 */
	protected function getModalsHTML() {
		$modal_date_html = $modal_quality_html = '';
		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
			$modal_date = new xoctReportingModalGUI($this, xoctReportingModalGUI::REPORTING_TYPE_DATE);
			$modal_date_html = $modal_date->getHTML();
		}
		if (xoctConf::getConfig(xoctConf::F_REPORT_QUALITY)) {
			$modal_quality = new xoctReportingModalGUI($this, xoctReportingModalGUI::REPORTING_TYPE_QUALITY);
			$modal_quality_html = $modal_quality->getHTML();
		}

		return $modal_date_html . $modal_quality_html;
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
     * @param xoctEvent $xoctEvent
     *
     * @return array
     * @throws xoctException
     */
	protected function getStreamingData(xoctEvent $xoctEvent) {
		$publication_player = $xoctEvent->getFirstPublicationMetadataForUsage(xoctPublicationUsage::getUsage(xoctPublicationUsage::USAGE_PLAYER));

		if (empty($publication_player->getMedia())) {
		    throw new xoctException(xoctException::NO_STREAMING_DATA);
        }
		// Multi stream
		$medias = array_values(array_filter($publication_player->getMedia(), function (xoctMedia $media) {
			return (strpos($media->getMediatype(), xoctMedia::MEDIA_TYPE_VIDEO) !== false
				&& in_array(xoctPublicationUsage::USAGE_ENGAGE_STREAMING, $media->getTags()));
		}));

		/**
		 * @var xoctAttachment[] $previews
		 */
		$previews = array_filter($publication_player->getAttachments(), function (xoctAttachment $attachment) {
			return (strpos($attachment->getFlavor(), '/player+preview') !== false);
		});

		$previews = array_reduce($previews, function (array &$previews, xoctAttachment $preview) {
			$previews[explode("/", $preview->getFlavor())[0]] = $preview;
			return $previews;
		}, []);

		$duration = 0;

		$id = $xoctEvent->getIdentifier();

		$streams = array_map(function (xoctMedia $media) use (&$duration, &$previews, &$id) {
			$url = xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS) ? xoctSecureLink::sign($media->getUrl()) : $media->getUrl();

			$duration = $duration ?: $media->getDuration();

			$preview_url = $previews[$media->getRole()];
			if ($preview_url !== null) {
				$preview_url = xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS) ? xoctSecureLink::sign($preview_url->getUrl()) : $preview_url->getUrl();
			} else {
				$preview_url = "";
			}

			if (xoctConf::getConfig(xoctConf::F_USE_STREAMING)) {

				$smil_url_identifier = ($media->getRole() !== xoctMedia::ROLE_PRESENTATION ? "_presenter" : "_presentation");
				$streaming_server_url = xoctConf::getConfig(xoctConf::F_STREAMING_URL);
				$hls_url = $streaming_server_url . "/smil:engage-player_" . $id . $smil_url_identifier . ".smil/playlist.m3u8";
				$dash_url = $streaming_server_url . "/smil:engage-player_" . $id . $smil_url_identifier . ".smil/manifest_mpm4sav_mvlist.mpd";

				if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
					// TODO: move this responsibility
					$valid_until = null;
					if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT)) {
						$duration_in_seconds = $duration / 1000;
						$additional_time_percent = xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT) / 100;
						$valid_until = gmdate("Y-m-d\TH:i:s\Z", time() + $duration_in_seconds + $duration_in_seconds * $additional_time_percent);
					}

					$hls_url = xoctSecureLink::sign($hls_url, $valid_until);
					$dash_url = xoctSecureLink::sign($dash_url, $valid_until);
				}

				return [
					"type" => xoctMedia::MEDIA_TYPE_VIDEO,
					"content" => ($media->getRole() !== xoctMedia::ROLE_PRESENTATION ? self::ROLE_MASTER : self::ROLE_SLAVE),
					"sources" => [
						"hls" => [
							[
								"src" => $hls_url,
								"mimetype" => "application/x-mpegURL"
							],
						],
						"dash" => [
							[
								"src" => $dash_url,
								"mimetype" => "application/dash+xml"
							]
						]
					],
					"preview" => $preview_url
				];
			} else {
				return [
					"type" => xoctMedia::MEDIA_TYPE_VIDEO,
					"content" => ($media->getRole() !== xoctMedia::ROLE_PRESENTATION ? self::ROLE_MASTER : self::ROLE_SLAVE),
					"sources" => [
						"mp4" => [
							[
								"src" => $url,
								"mimetype" => $media->getMediatype(),
								"res" => [
									"w" => $media->getWidth(),
									"h" => $media->getHeight()
								]
							]
						]

					],
					"preview" => $preview_url
				];
			}
		}, $medias);

		if (xoctConf::getConfig(xoctConf::F_USE_STREAMING)) {

			$filteredStreams = array();
			foreach ($streams as $stream) {
				$filteredStreams[$stream['content']] = $stream;
			}

			$streams = array();
			foreach ($filteredStreams as $stream) {
				$streams[] = $stream;
			}
		}

		$segment_publication = xoctPublicationUsage::find(xoctPublicationUsage::USAGE_SEGMENTS);
		if ($segment_publication) {
			$segment_flavor = $segment_publication->getFlavor();
			$publication_usage_segments = xoctPublicationUsage::getUsage(xoctPublicationUsage::USAGE_SEGMENTS);
			$attachments = $publication_usage_segments->getMdType()
			== xoctPublicationUsage::MD_TYPE_PUBLICATION_ITSELF ? $xoctEvent->getFirstPublicationMetadataForUsage($publication_usage_segments)
				->getAttachments() : $xoctEvent->getPublicationMetadataForUsage($publication_usage_segments);

			$segments = array_filter($attachments, function (xoctAttachment $attachment) use (&$segment_flavor) {
				return strpos($attachment->getFlavor(), $segment_flavor) !== false;
			});

			$segments = array_reduce($segments, function (array &$segments, xoctAttachment $segment) {
				if (!isset($segments[$segment->getRef()])) {
					$segments[$segment->getRef()] = [];
				}
				$segments[$segment->getRef()][$segment->getFlavor()] = $segment;

				return $segments;
			}, []);

			ksort($segments);
			$frameList = array_values(array_map(function (array $segment) {

				if (xoctConf::getConfig(xoctConf::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS)) {
					/**
					 * @var xoctAttachment[] $segment
					 */
					$high = $segment[xoctMetadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW_HIGHRES];
					$low = $segment[xoctMetadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW_LOWRES];
					if ($high === null || $low === null) {
						$high = $segment[xoctMetadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW_HIGHRES];
						$low = $segment[xoctMetadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW_LOWRES];
					}

					$time = substr($high->getRef(), strpos($high->getRef(), ";time=") + 7, 8);
					$time = new DateTime("1970-01-01 $time", new DateTimeZone("UTC"));
					$time = $time->getTimestamp();

					$high_url = $high->getUrl();
					$low_url = $low->getUrl();
					if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
						$high_url = xoctSecureLink::sign($high_url);
						$low_url = xoctSecureLink::sign($low_url);
					}

					return [
						"id" => "frame_" . $time,
						"mimetype" => $high->getMediatype(),
						"time" => $time,
						"url" => $high_url,
						"thumb" => $low_url
					];
				} else {
					$preview = $segment[xoctMetadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW];

					if ($preview === null) {
						$preview = $segment[xoctMetadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW];
					}

					$time = substr($preview->getRef(), strpos($preview->getRef(), ";time=") + 7, 8);
					$time = new DateTime("1970-01-01 $time", new DateTimeZone("UTC"));
					$time = $time->getTimestamp();

					$url = $preview->getUrl();
					if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
						$url = xoctSecureLink::sign($url);
					}

					return [
						"id" => "frame_" . $time,
						"mimetype" => $preview->getMediatype(),
						"time" => $time,
						"url" => $url,
						"thumb" => $url
					];
				}
			}, $segments));
		}

		$data = [
			"streams" => $streams,
			"metadata" => [
				"title" => $xoctEvent->getTitle(),
				"duration" => $duration
			]
		];

		if (isset($frameList)) {
			$data['frameList'] = $frameList;
		}

		return $data;
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

	/**
	 * @param xoctEvent $xoctEvent
	 * @return array
	 * @throws xoctException
	 */
	protected function getLiveStreamingData(xoctEvent $xoctEvent) {
		$episode_json = xoctRequest::root()->episodeJson($xoctEvent->getIdentifier())->get([], '', xoctConf::getConfig(xoctConf::F_PRESENTATION_NODE));
		$episode_data = json_decode($episode_json, true);
		$media_package = $episode_data['search-results']['result']['mediapackage'];

		$streams = [];
		if( isset($media_package['media']['track'][0]))
        {
            foreach ($media_package['media']['track'] as $track) {
                $streams[] = [
                    "content" => (strpos($track['type'], self::ROLE_MASTER) !== false ? self::ROLE_MASTER : self::ROLE_SLAVE),
                    "sources" => [
                        "hls" => [
                            [
                                "src" => $track['url'],
                                "mimetype" => $track['mimetype'],
                            ]
                        ]
                    ]
                ];
            }
        } else {
            $track = $media_package['media']['track'];
            $streams[] = [
                "content" => self::ROLE_MASTER,
                "sources" => [
                    "hls" => [
                        [
                            "src" => $track['url'],
                            "mimetype" => $track['mimetype'],
                        ]
                    ]
                ]
            ];
        }

		return [
			"streams" => $streams,
			"metadata" => [
				"title" => $xoctEvent->getTitle(),
			],
		];
	}
}
