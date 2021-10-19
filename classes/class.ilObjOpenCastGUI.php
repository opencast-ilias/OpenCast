<?php
require_once __DIR__ . '/../vendor/autoload.php';

use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\API\Group\Group;
use srag\Plugins\Opencast\Cache\Service\DB\DBCacheService;
use srag\Plugins\Opencast\Cache\CacheFactory;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;

/**
 * User Interface class for example repository object.
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version           1.0.00
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjOpenCastGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjOpenCastGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 *
 */
class ilObjOpenCastGUI extends ilObjectPluginGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const CMD_SHOW_CONTENT = 'showContent';
	const CMD_REDIRECT_SETTING = 'redirectSettings';
	const TAB_EVENTS = 'series';
	const TAB_SETTINGS = 'settings';
	const TAB_INFO = 'info_short';
	const TAB_GROUPS = 'groups';
	/**
	 * @var ilObjOpenCast
	 */
	//	public $object;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilObjOpenCast
	 */
	public $object;

    private function cleanUpDBCache()
    {
        if (xoctConf::getConfig(xoctConf::F_ACTIVATE_CACHE) == xoctConf::CACHE_DATABASE) {
            $bm = microtime(true);
            DBCacheService::cleanup(self::dic()->databaseCore());
            self::dic()->logger()->root()->info('cache cleanup done in ' . round((microtime(true) - $bm) * 1000) . 'ms');
        }
    }

    protected function afterConstructor() {
	}


	/**
	 * @return string
	 */
	final function getType() {
		return ilOpenCastPlugin::PLUGIN_ID;
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		$this->{$cmd}();
	}


	public function executeCommand() {
		$this->checkPermission('read');
		try {
			xoctConf::setApiSettings();
			$next_class = self::dic()->ctrl()->getNextClass();
			$cmd = self::dic()->ctrl()->getCmd();
			if (xoct::isIlias6()) {
			    self::dic()->ui()->mainTemplate()->loadStandardTemplate();
            } else {
                self::dic()->mainTemplate()->getStandardTemplate();
            }

			switch ($next_class) {
                case 'xoctivtgroupparticipantgui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctIVTGroupParticipantGUI($xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctinvitationgui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctInvitationGUI($xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctchangeownergui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctChangeOwnerGUI($xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctseriesgui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctSeriesGUI($this->object, $xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'xocteventgui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctEventGUI = new xoctEventGUI($this, $xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctEventGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctivtgroupgui':
                    $xoctOpenCast = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctIVTGroupGUI($xoctOpenCast);
                    self::dic()->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'ilpermissiongui':
                    $this->initHeader(false);
                    parent::executeCommand();
                    break;
                default:
					// workaround for object deletion; 'parent::executeCommand()' shows the template and leads to "Headers already sent" error
					if ($next_class == "" && $cmd == 'deleteObject') {
						$this->deleteObject();
						break;
					}
					parent::executeCommand();
					break;
			}
		} catch (xoctException $e) {
			ilUtil::sendFailure($e->getMessage());
            if (!$this->creation_mode) {
                $this->showMainTemplate();
            }
		}

        $this->cleanUpDBCache();
    }

    /**
     * @return ilObjOpenCast
     */
	public function getObject() : ilObjOpenCast
    {
        return $this->object ?: new ilObjOpenCast();
    }

    /**
     *
     */
	protected function showMainTemplate()
    {
        if (xoct::isIlias6()) {
            self::dic()->mainTemplate()->printToStdout();
        } else {
            self::dic()->mainTemplate()->show();
        }
    }


	protected function showContent() {
		self::dic()->ctrl()->redirectByClass(xoctEventGUI::class);
	}


	protected function redirectSettings() {
		self::dic()->ctrl()->redirectByClass(xoctSeriesGUI::class, xoctSeriesGUI::CMD_EDIT);
	}


	/**
	 * @return string
	 */
	public function getAfterCreationCmd() {
		return self::CMD_SHOW_CONTENT;
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return self::CMD_SHOW_CONTENT;
	}


    /**
     * @return bool
     * @throws DICException
     */
	protected function setTabs() {
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		if (!$xoctOpenCast instanceof xoctOpenCast) {
			return false;
		}

		self::dic()->tabs()->addTab(self::TAB_EVENTS, self::plugin()->translate('tab_event_index'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_STANDARD));
		self::dic()->tabs()->addTab(self::TAB_INFO, self::plugin()->translate('tab_info'), self::dic()->ctrl()->getLinkTarget($this, 'infoScreen'));

		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_SETTINGS)) {
			self::dic()->tabs()->addTab(self::TAB_SETTINGS, self::plugin()->translate('tab_series_settings'), self::dic()->ctrl()->getLinkTargetByClass(xoctSeriesGUI::class, xoctSeriesGUI::CMD_EDIT_GENERAL));
		}

		if ($xoctOpenCast->getPermissionPerClip() && ilObjOpenCastAccess::hasPermission('read')) {
			self::dic()->tabs()->addTab(self::TAB_GROUPS, self::plugin()->translate('tab_groups'), self::dic()->ctrl()->getLinkTarget(new xoctIVTGroupGUI()));
		}
		if (self::dic()->user()->getId() == 6 AND ilObjOpenCast::DEV) {
			self::dic()->tabs()->addTab('migrate_event', self::plugin()->translate('tab_migrate_event'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'search'));
			self::dic()->tabs()->addTab('list_all', self::plugin()->translate('tab_list_all'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'listAll'));
		}

		if ($this->checkPermissionBool("edit_permission")) {
			self::dic()->tabs()->addTab("perm_settings", self::dic()->language()->txt("perm_settings"), self::dic()->ctrl()->getLinkTargetByClass(array(
				get_class($this),
				"ilpermissiongui",
			), "perm"));
		}

		return true;
	}


    /**
     * @param string $a_new_type
     *
     * @return array
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
	protected function initCreationForms($a_new_type) {
		if (!ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id'])) {
			ilUtil::sendFailure(self::plugin()->translate('msg_creation_failed'), true);
			ilUtil::redirect('/');
		}
		self::dic()->ctrl()->setParameter($this, 'new_type', ilOpenCastPlugin::PLUGIN_ID);

		return array( self::CFORM_NEW => $this->initCreateForm($a_new_type) );
	}


    /**
     * @param string     $type
     * @param bool|false $from_post
     *
     * @return xoctSeriesFormGUI
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
	public function initCreateForm($type, $from_post = false) {
		$creation_form = new xoctSeriesFormGUI($this, new xoctOpenCast());
		if ($from_post) {
			$creation_form->setValuesByPost();
		} else {
			$creation_form->fillForm();
			if (ilObjOpenCast::DEV) {
				$creation_form->fillFormRandomized();
			}
		}

		return $creation_form->getAsPropertyFormGui();
	}


    /**
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
	public function save() {
		$creation_form = $this->initCreateForm($this->getType(), true);

		if ($_POST['channel_type'] == xoctSeriesFormGUI::EXISTING_NO) {
			$xoctAclStandardSets = new xoctAclStandardSets();
			$creation_form->getSeries()->setAccessPolicies($xoctAclStandardSets->getAcls());
		}

		if ($return = $creation_form->saveObject()) {
			$this->saveObject($return[0], $return[1], $creation_form->getInput(xoctSeriesFormGUI::F_CHANNEL_TYPE));
		} else {
			self::dic()->mainTemplate()->setContent($creation_form->getHTML());
		}
	}


    /**
     * @param ilObject $newObj
     *
     * @throws DICException
     * @throws xoctException
     */
	public function afterSave(ilObject $newObj) {
		/**
		 * @var $cast xoctOpenCast
		 */
		// set object id for xoctOpenCast object
		$args = func_get_args();
		$additional_args = $args[1];
		$cast = $additional_args[0];
        $is_memberupload_enabled = $additional_args[1];
        $channel_type = $additional_args[2];
		$cast->setObjId($newObj->getId());
		if (xoctOpenCast::where(array( 'obj_id' => $newObj->getId() ))->hasSets()) {
			$cast->update();
		} else {
			$cast->create();
		}

		// set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
		$producers = ilObjOpenCastAccess::getProducersForRefID($newObj->getRefId());
		$producers[] = xoctUser::getInstance(self::dic()->user());

		try {
			$ilias_producers = Group::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
			$ilias_producers->addMembers($producers);
		} catch (xoctException $e) {
		}
		$series = $cast->getSeries();
        $series->addProducers($producers, true);
        // PLOPENCAST-159
        if ($channel_type == xoctSeriesFormGUI::EXISTING_NO) {
            $series->addOrganizer(ilObjOpencast::_getParentCourseOrGroup($_GET['ref_id'])->getTitle(), true);
            $series->addContributor(self::dic()->user()->getFirstname() . ' ' . self::dic()->user()->getLastname(), true);
        }
        $series->update();

		if ($cast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('msg_info_multiple_aftersave'), true);
		}

		// checkbox from creation gui to activate "upload" permission for members
		if ($is_memberupload_enabled) {
			ilObjOpenCastAccess::activateMemberUpload($newObj->getRefId());
		}

		$newObj->setTitle($series->getTitle());
		$newObj->setDescription($series->getDescription());
		$newObj->update();

        SeriesWorkflowParameterRepository::getInstance()->syncAvailableParameters($newObj->getId());

        parent::afterSave($newObj);
	}


    /**
     * @param bool $render_locator
     *
     * @return xoctOpenCast
     * @throws DICException
     * @throws xoctException
     */
	protected function initHeader($render_locator = true) {
		if ($render_locator && !self::dic()->ctrl()->isAsynch()) {
			$this->setLocator();
		}

		/**
		 * @var $xoctOpenCast xoctOpenCast
		 * @var $xoctSeries   xoctSeries
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		if (self::dic()->ctrl()->isAsynch()) {
		    return $xoctOpenCast;
        }

		if ($xoctOpenCast instanceof xoctOpenCast && $this->object) {
			self::dic()->mainTemplate()->setTitle($this->object->getTitle());
			self::dic()->mainTemplate()->setDescription($this->object->getDescription());
			if (self::dic()->access()->checkAccess('read', '', $_GET['ref_id'])) {
				self::dic()->history()->addItem($_GET['ref_id'], self::dic()->ctrl()->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), $this->object->getTitle());
			}
			require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
			$list_gui = ilObjectListGUIFactory::_getListGUIByType(ilOpenCastPlugin::PLUGIN_ID);
			/**
			 * @var $list_gui ilObjOpenCastListGUI
			 */
			if (!$xoctOpenCast->isOnline()) {
				self::dic()->mainTemplate()->setAlertProperties($list_gui->getAlertProperties());
			}
		} else {
			self::dic()->mainTemplate()->setTitle(self::plugin()->translate('series_create'));
		}
		self::dic()->mainTemplate()->setTitleIcon(ilObjOpenCast::_getIcon($this->object_id));
		self::dic()->mainTemplate()->setPermanentLink(ilOpenCastPlugin::PLUGIN_ID, $_GET['ref_id']);

		return $xoctOpenCast;
	}


	/**
	 * show information screen
	 */
	function infoScreen() {
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 * @var $item         xoctOpenCast
		 * @var $tree         ilTree
		 */
		self::dic()->tabs()->setTabActive("info_short");
		$this->initHeader(false);
		$this->checkPermission("visible");

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->enablePrivateNotes();
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		if ($refs = $xoctOpenCast->getDuplicatesOnSystem()) {
			$info->addSection(self::plugin()->translate('info_linked_items'));
			$i = 1;
			foreach ($refs as $ref) {
				$parent = self::dic()->tree()->getParentId($ref);
				$info->addProperty(($i) . '. '
					. self::plugin()->translate('info_linked_item'), ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)), ilLink::_getStaticLink($parent));
				$i ++;
			}
		}

        if ($xoctOpenCast->getVideoPortalLink() && $xoctOpenCast->getSeries()->isPublishedOnVideoPortal()) {
		    $info->addSection(self::plugin()->translate('series_links'));
		    $info->addProperty(self::plugin()->translate('series_video_portal_link', '', [xoctConf::getConfig(xoctConf::F_VIDEO_PORTAL_TITLE)]), $xoctOpenCast->getVideoPortalLink());
        }

		// general information
		self::dic()->language()->loadLanguageModule("meta");

		$this->addInfoItems($info);

		// forward the command
		self::dic()->ctrl()->forwardCommand($info);
	}


	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 *
	 * @param bool $a_error
	 */
	public function deleteObject($a_error = false) {
		if ($_GET["item_ref_id"] != "") {
			$_POST["id"] = array( $_GET["item_ref_id"] );
		}

		if (is_array($_POST["id"])) {
			foreach ($_POST["id"] as $idx => $id) {
				$_POST["id"][$idx] = (int)$id;
			}
		}

		// SAVE POST VALUES (get rid of this
		ilSession::set("saved_post", $_POST["id"]);

		if (!$this->showDeleteConfirmation($_POST["id"], $a_error)) {
			self::dic()->ctrl()->returnToParent($this);
		}
	}


	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 */
	function showDeleteConfirmation($a_ids, $a_supress_message = false)
	{
		if (!is_array($a_ids) || count($a_ids) == 0) {
			ilUtil::sendFailure(self::dic()->language()->txt("no_checkbox"), true);

			return false;
		}

		// Remove duplicate entries
		$a_ids = array_unique((array)$a_ids);

		include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
		$cgui = new ilConfirmationGUI();

		if (!$a_supress_message) {
			$msg = self::dic()->language()->txt("info_delete_sure");

			if (!self::dic()->settings()->get('enable_trash')) {
				$msg .= "<br/>" . self::dic()->language()->txt("info_delete_warning_no_trash");
			}

			$cgui->setHeaderText($msg);
		}
		$cgui->setFormAction(self::dic()->ctrl()->getFormAction($this));
		$cgui->setCancel(self::dic()->language()->txt("cancel"), "cancelDelete");
		$cgui->setConfirm(self::dic()->language()->txt("confirm"), "confirmedDelete");

		$form_name = "cgui_" . md5(uniqid());
		$cgui->setFormName($form_name);

		$deps = array();
		foreach ($a_ids as $ref_id) {
			$obj_id = ilObject::_lookupObjId($ref_id);
			$type = ilObject::_lookupType($obj_id);
			$title = call_user_func(array( ilObjectFactory::getClassByType($type), '_lookupTitle' ), $obj_id);
			$alt = self::dic()->language()->txt("icon") . " " . ilPlugin::lookupTxt("rep_robj", $type, "obj_" . $type);

			$title .= $this->handleMultiReferences($obj_id, $ref_id, $form_name);

			$cgui->addItem("id[]", $ref_id, $title, ilObject::_getIcon($obj_id, "small", $type), $alt);

			ilObject::collectDeletionDependencies($deps, $ref_id, $obj_id, $type);
		}
		$deps_html = "";

		if (is_array($deps) && count($deps) > 0) {
			include_once("./Services/Repository/classes/class.ilRepDependenciesTableGUI.php");
			$tab = new ilRepDependenciesTableGUI($deps);
			$deps_html = "<br/><br/>" . $tab->getHTML();
		}

		self::dic()->mainTemplate()->setContent($cgui->getHTML() . $deps_html);

		return true;
	}


    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     *
     * @param int $a_obj_id
     * @param int $a_ref_id
     * @param string $a_form_name
     *
     * @return string
     * @throws Exception
     */
	function handleMultiReferences($a_obj_id, $a_ref_id, $a_form_name)
	{
		// process

		/** @var xoctOpenCast $xoctOpenCast */
		$xoctOpenCast = xoctOpenCast::find($a_obj_id);
		if ($all_refs = $xoctOpenCast->getDuplicatesOnSystem()) {
			self::dic()->language()->loadLanguageModule("rep");

			$may_delete_any = 0;
			$counter = 0;
			$items = array();
			foreach ($all_refs as $mref_id) {
				// not the already selected reference, no refs from trash
				if ($mref_id != $a_ref_id && !self::dic()->tree()->isDeleted($mref_id)) {
					if (self::dic()->access()->checkAccess("read", "", $mref_id)) {
						$may_delete = false;
						if (self::dic()->access()->checkAccess("delete", "", $mref_id)) {
							$may_delete = true;
							$may_delete_any ++;
						}

						$items[] = array(
							"id" => $mref_id,
							"path" => array_shift($this->buildPath(array( $mref_id ))),
							"delete" => $may_delete
						);
					} else {
						$counter ++;
					}
				}
			}

			// render

			$tpl = new ilTemplate("tpl.rep_multi_ref.html", true, true, "Services/Repository");

			$tpl->setVariable("TXT_INTRO", self::dic()->language()->txt("rep_multiple_reference_deletion_intro"));

			if ($may_delete_any) {
				$tpl->setVariable("TXT_INSTRUCTION", self::dic()->language()->txt("rep_multiple_reference_deletion_instruction"));
			}

			if ($items) {
				$var_name = "mref_id[]";

				foreach ($items as $item) {
					if ($item["delete"]) {
						$tpl->setCurrentBlock("cbox");
						$tpl->setVariable("ITEM_NAME", $var_name);
						$tpl->setVariable("ITEM_VALUE", $item["id"]);
						$tpl->parseCurrentBlock();
					} else {
						$tpl->setCurrentBlock("item_info");
						$tpl->setVariable("TXT_ITEM_INFO", self::dic()->language()->txt("rep_no_permission_to_delete"));
						$tpl->parseCurrentBlock();
					}

					$tpl->setCurrentBlock("item");
					$tpl->setVariable("ITEM_TITLE", $item["path"]);
					$tpl->parseCurrentBlock();
				}

				if ($may_delete_any > 1) {
					$tpl->setCurrentBlock("cbox");
					$tpl->setVariable("ITEM_NAME", "sall_" . $a_ref_id);
					$tpl->setVariable("ITEM_VALUE", "");
					$tpl->setVariable("ITEM_ADD", " onclick=\"il.Util.setChecked('" . $a_form_name . "', '" . $var_name . "', document."
						. $a_form_name . ".sall_" . $a_ref_id . ".checked)\"");
					$tpl->parseCurrentBlock();

					$tpl->setCurrentBlock("item");
					$tpl->setVariable("ITEM_TITLE", self::dic()->language()->txt("select_all"));
					$tpl->parseCurrentBlock();
				}
			}

			if ($counter) {
				$tpl->setCurrentBlock("add_info");
				$tpl->setVariable("TXT_ADDITIONAL_INFO", sprintf(self::dic()->language()->txt("rep_object_references_cannot_be_read"), $counter));
				$tpl->parseCurrentBlock();
			}

			return $tpl->get();
		}
	}


	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 *
	 * @param    array $ref_ids
	 *
	 * @return    array
	 */
	protected function buildPath($ref_ids)
	{
		include_once 'Services/Link/classes/class.ilLink.php';

		if (!count($ref_ids)) {
			return false;
		}

		$result = array();
		foreach ($ref_ids as $ref_id) {
			$path = "";
			$path_full = self::dic()->tree()->getPathFull($ref_id);
			foreach ($path_full as $idx => $data) {
				if ($idx) {
					$path .= " &raquo; ";
				}
				if ($ref_id != $data['ref_id']) {
					$path .= $data['title'];
				} else {
					$path .= ('<a target="_top" href="' . ilLink::_getLink($data['ref_id'], $data['type']) . '">' . $data['title'] . '</a>');
				}
			}

			$result[] = $path;
		}

		return $result;
	}
}
