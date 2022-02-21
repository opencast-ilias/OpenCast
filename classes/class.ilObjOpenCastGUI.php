<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ILIAS\DI\Container;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Cache\Service\DB\DBCacheService;
use srag\Plugins\Opencast\Model\Group\Group;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequestPayload;
use srag\Plugins\Opencast\UI\LegacyFormWrapper;
use srag\Plugins\Opencast\Util\DI\OpencastDIC;

/**
 * User Interface class for example repository object.
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.00
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 * @ilCtrl_isCalledBy ilObjOpenCastGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjOpenCastGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 */
class ilObjOpenCastGUI extends ilObjectPluginGUI
{

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    const CMD_SHOW_CONTENT = 'showContent';
    const CMD_REDIRECT_SETTING = 'redirectSettings';
    const TAB_EVENTS = 'series';
    const TAB_SETTINGS = 'settings';
    const TAB_INFO = 'info_short';
    const TAB_GROUPS = 'groups';
    const TAB_EULA = "eula";

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
    /**
     * @var OpencastDIC
     */
    private $opencast_dic;
    /**
     * @var Container
     */
    private $ilias_dic;

    private function cleanUpDBCache()
    {
        if (xoctConf::getConfig(xoctConf::F_ACTIVATE_CACHE) == xoctConf::CACHE_DATABASE) {
            $bm = microtime(true);
            DBCacheService::cleanup($this->ilias_dic->database());
            $this->ilias_dic->logger()->root()->info('cache cleanup done in ' . round((microtime(true) - $bm) * 1000) . 'ms');
        }
    }

    protected function afterConstructor()
    {
        global $DIC;
        $this->ilias_dic = $DIC;
        $this->opencast_dic = OpencastDIC::getInstance();
    }


    /**
     * @return string
     */
    final function getType()
    {
        return ilOpenCastPlugin::PLUGIN_ID;
    }


    /**
     * @param $cmd
     */
    public function performCommand($cmd)
    {
        $this->{$cmd}();
    }


    public function executeCommand()
    {
        $this->checkPermission('read');
        try {
            xoctConf::setApiSettings();
            $next_class = $this->ilias_dic->ctrl()->getNextClass();
            $cmd = $this->ilias_dic->ctrl()->getCmd();
            if (xoct::isIlias6()) {
                $this->ilias_dic->ui()->mainTemplate()->loadStandardTemplate();
            } else {
                $this->ilias_dic->ui()->mainTemplate()->getStandardTemplate();
            }

            switch ($next_class) {
                case 'xoctivtgroupparticipantgui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctIVTGroupParticipantGUI = new xoctIVTGroupParticipantGUI($objectSettings);
                    $this->ilias_dic->ctrl()->forwardCommand($xoctIVTGroupParticipantGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctinvitationgui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctInvitationGUI = new xoctInvitationGUI(
                        $objectSettings,
                        $this->opencast_dic->event_repository(),
                        $this->opencast_dic->acl_utils()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctInvitationGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctchangeownergui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctChangeOwnerGUI = new xoctChangeOwnerGUI(
                        $objectSettings,
                        $this->opencast_dic->event_repository(),
                        $this->opencast_dic->acl_utils()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctChangeOwnerGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctseriesgui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctSeriesGUI($this->object,
                        $this->opencast_dic->series_form_builder(),
                        $this->opencast_dic->series_repository(),
                        $this->opencast_dic->workflow_parameter_series_repository(),
                        $this->opencast_dic->workflow_parameter_conf_repository(),
                        $this->opencast_dic->paella_config_upload_handler()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case 'xocteventgui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctEventGUI = new xoctEventGUI(
                        $this,
                        $objectSettings,
                        $this->opencast_dic->event_repository(),
                        $this->opencast_dic->event_form_builder(),
                        $this->opencast_dic->event_table_builder(),
                        $this->opencast_dic->workflow_repository(),
                        $this->opencast_dic->acl_utils(),
                        $this->opencast_dic->series_repository(),
                        $this->opencast_dic->upload_handler(),
                        $this->opencast_dic->paella_config_storage_service(),
                        $this->ilias_dic);
                    $this->ilias_dic->ctrl()->forwardCommand($xoctEventGUI);
                    $this->showMainTemplate();
                    break;
                case 'xoctivtgroupgui':
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctIVTGroupGUI = new xoctIVTGroupGUI($objectSettings);
                    $this->ilias_dic->ctrl()->forwardCommand($xoctIVTGroupGUI);
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
            $this->ilias_dic->logger()->root()->error($e->getMessage());
            $this->ilias_dic->logger()->root()->error($e->getTraceAsString());
            if (!$this->creation_mode) {
                $this->showMainTemplate();
            }
        }

        $this->cleanUpDBCache();
    }

    /**
     * @return ilObjOpenCast
     */
    public function getObject(): ilObjOpenCast
    {
        return $this->object ?: new ilObjOpenCast();
    }

    /**
     *
     */
    protected function showMainTemplate()
    {
        if (xoct::isIlias6()) {
            $this->ilias_dic->ui()->mainTemplate()->printToStdout();
        } else {
            $this->ilias_dic->ui()->mainTemplate()->show();
        }
    }


    protected function showContent()
    {
        $this->ilias_dic->ctrl()->redirectByClass(xoctEventGUI::class);
    }


    protected function redirectSettings()
    {
        $this->ilias_dic->ctrl()->redirectByClass(xoctSeriesGUI::class, xoctSeriesGUI::CMD_EDIT);
    }


    /**
     * @return string
     */
    public function getAfterCreationCmd()
    {
        return self::CMD_SHOW_CONTENT;
    }


    /**
     * @return string
     */
    function getStandardCmd()
    {
        return self::CMD_SHOW_CONTENT;
    }


    /**
     * @return bool
     * @throws DICException
     */
    protected function setTabs()
    {
        /**
         * @var $objectSettings ObjectSettings
         */
        $objectSettings = ObjectSettings::find($this->obj_id);
        if (!$objectSettings instanceof ObjectSettings) {
            return false;
        }

        $this->ilias_dic->tabs()->addTab(self::TAB_EVENTS, self::plugin()->translate('tab_event_index'), $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_STANDARD));
        $this->ilias_dic->tabs()->addTab(self::TAB_INFO, self::plugin()->translate('tab_info'), $this->ilias_dic->ctrl()->getLinkTarget($this, 'infoScreen'));

        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_SETTINGS)) {
            $this->ilias_dic->tabs()->addTab(self::TAB_SETTINGS, self::plugin()->translate('tab_series_settings'), $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctSeriesGUI::class, xoctSeriesGUI::CMD_EDIT_GENERAL));
        }

        if ($objectSettings->getPermissionPerClip() && ilObjOpenCastAccess::hasPermission('read')) {
            $this->ilias_dic->tabs()->addTab(self::TAB_GROUPS, self::plugin()->translate('tab_groups'), $this->ilias_dic->ctrl()->getLinkTarget(new xoctIVTGroupGUI()));
        }
        if ($this->ilias_dic->user()->getId() == 6 and ilObjOpenCast::DEV) {
            $this->ilias_dic->tabs()->addTab('migrate_event', self::plugin()->translate('tab_migrate_event'), $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'search'));
            $this->ilias_dic->tabs()->addTab('list_all', self::plugin()->translate('tab_list_all'), $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'listAll'));
        }

        if ($this->checkPermissionBool("edit_permission")) {
            $this->ilias_dic->tabs()->addTab("perm_settings", $this->ilias_dic->language()->txt("perm_settings"), $this->ilias_dic->ctrl()->getLinkTargetByClass(array(
                get_class($this),
                "ilpermissiongui",
            ), "perm"));
        }

        // ToDo: Why does this access check not work?
        if(ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_UPLOAD)) {
            $this->ilias_dic->tabs()->addTab(self::TAB_EULA, self::plugin()->translate("eula"),
                $this->ilias_dic->ctrl()->getLinkTarget($this, "showEula"));
        }
        return true;
    }

    private function showEula()
    {
        self::dic()->tabs()->activateTab("eula");
        self::dic()->ui()->mainTemplate()->setContent(xoctConf::getConfig(xoctConf::F_EULA));
    }

    /**
     * @param string $a_new_type
     * @return array
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
    protected function initCreationForms($a_new_type)
    {
        if (!ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id'])) {
            ilUtil::sendFailure(self::plugin()->translate('msg_creation_failed'), true);
            ilUtil::redirect('/');
        }
        $this->ilias_dic->ctrl()->setParameter($this, 'new_type', ilOpenCastPlugin::PLUGIN_ID);

        return array(self::CFORM_NEW => $this->initCreateForm($a_new_type));
    }

    /**
     * @param string $type
     * @param bool|false $from_post
     */
    public function initCreateForm($type, $from_post = false)
    {
        return new LegacyFormWrapper(
            $this->ilias_dic->ui()->renderer()->render(
                $this->buildUIForm()
            )
        );
    }

    private function buildUIForm(): Form
    {
        return $this->opencast_dic->series_form_builder()->create(
            $this->ilias_dic->ctrl()->getFormAction($this, 'save')
        );
    }

    /**
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
    public function save()
    {
        $creation_form = $this->buildUIForm()->withRequest($this->ilias_dic->http()->request());
        $data = $creation_form->getData();

        if (!$data) {
            $this->ilias_dic->ui()->mainTemplate()->setContent($this->ilias_dic->ui()->renderer()->render($creation_form));
            return;
        }

        $this->saveObject($data);
    }


    /**
     * @param ilObject $newObj
     * @throws DICException
     * @throws xoctException
     */
    public function afterSave(ilObject $newObj)
    {
        /**
         * @var $settings ObjectSettings
         */
        // set object id for objectSettings object
        $args = func_get_args();
        $additional_args = $args[1][0];
        /** @var ObjectSettings $settings */
        $settings = $additional_args['settings']['object'];
        /** @var Metadata $metadata */
        $metadata = $additional_args['series_type']['metadata'];
        /** @var string|false $series_id */
        $series_id = $additional_args['series_type']['channel_id'];
        /** @var bool $is_memberupload_enabled */
        $is_memberupload_enabled = $additional_args['member_upload'];
        /** @var int $perm_tpl_id */
        $perm_tpl_id = $additional_args['settings']['permission_template'];

        // set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
        $producers = ilObjOpenCastAccess::getProducersForRefID($newObj->getRefId());
        $producers[] = xoctUser::getInstance($this->ilias_dic->user());

        try {
            $ilias_producers = Group::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
            $ilias_producers->addMembers($producers);
        } catch (xoctException $e) {
            self::dic()->log()->warning('Could not add producers to group while creating a series, msg: '
                . $e->getMessage());
        }

        $acl = $this->opencast_dic->acl_utils()->getStandardRolesACL();
        foreach ($producers as $producer) {
            $acl->merge($this->opencast_dic->acl_utils()->getUserRolesACL($producer));
        }

        if ($perm_tpl_id) {
            $acl = xoctPermissionTemplate::removeAllTemplatesFromAcls($acl);
            /** @var xoctPermissionTemplate $perm_tpl */
            $perm_tpl = xoctPermissionTemplate::find($perm_tpl_id);
            $acl = $perm_tpl->addToAcls(
                $acl,
                !$settings->getStreamingOnly(),
                $settings->getUseAnnotations()
            );
        }

        // TODO: do we need contributor / organizer?
        if (!$series_id) {
            $series_id = $this->opencast_dic->series_repository()->create(new CreateSeriesRequest(new CreateSeriesRequestPayload(
                $metadata,
                $acl
            )));
        } else {
            $metadata = $this->opencast_dic->series_repository()->find($series_id)->getMetadata();
        }

        $settings->setSeriesIdentifier($series_id);
        $settings->setObjId($newObj->getId());
        $settings->create();

        if ($settings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo(self::plugin()->translate('msg_info_multiple_aftersave'), true);
        }

        // checkbox from creation gui to activate "upload" permission for members
        if ($is_memberupload_enabled) {
            ilObjOpenCastAccess::activateMemberUpload($newObj->getRefId());
        }

        $newObj->setTitle($metadata->getField(MDFieldDefinition::F_TITLE)->getValue());
        $newObj->setDescription($metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue());
        $newObj->update();

        $this->opencast_dic->workflow_parameter_series_repository()->syncAvailableParameters($newObj->getId());

        parent::afterSave($newObj);
    }


    /**
     * @param bool $render_locator
     *
     * @return ObjectSettings
     * @throws DICException
     * @throws xoctException
     */
    protected function initHeader($render_locator = true)
    {
        if ($render_locator && !$this->ilias_dic->ctrl()->isAsynch()) {
            $this->setLocator();
        }

        /**
         * @var $objectSettings ObjectSettings
         */
        $objectSettings = ObjectSettings::find($this->obj_id);
        if ($this->ilias_dic->ctrl()->isAsynch()) {
            return $objectSettings;
        }

        if ($objectSettings instanceof ObjectSettings && $this->object) {
            $this->ilias_dic->ui()->mainTemplate()->setTitle($this->object->getTitle());
            $this->ilias_dic->ui()->mainTemplate()->setDescription($this->object->getDescription());
            if ($this->ilias_dic->access()->checkAccess('read', '', $_GET['ref_id'])) {
                // TODO: remove self::dic
                self::dic()->history()->addItem($_GET['ref_id'], $this->ilias_dic->ctrl()->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), $this->object->getTitle());
            }
            require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
            $list_gui = ilObjectListGUIFactory::_getListGUIByType(ilOpenCastPlugin::PLUGIN_ID);
            /**
             * @var $list_gui ilObjOpenCastListGUI
             */
            if (!$objectSettings->isOnline()) {
                $this->ilias_dic->ui()->mainTemplate()->setAlertProperties($list_gui->getAlertProperties());
            }
        } else {
            $this->ilias_dic->ui()->mainTemplate()->setTitle(self::plugin()->translate('series_create'));
        }
        $this->ilias_dic->ui()->mainTemplate()->setTitleIcon(ilObjOpenCast::_getIcon($this->object_id));
        $this->ilias_dic->ui()->mainTemplate()->setPermanentLink(ilOpenCastPlugin::PLUGIN_ID, $_GET['ref_id']);

        return $objectSettings;
    }


    /**
     * show information screen
     */
    function infoScreen()
    {
        /**
         * @var $objectSettings ObjectSettings
         * @var $item         ObjectSettings
         * @var $tree         ilTree
         */
        $this->ilias_dic->tabs()->setTabActive("info_short");
        $this->initHeader(false);
        $this->checkPermission("visible");

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);
        $info->enablePrivateNotes();
        $objectSettings = ObjectSettings::find($this->obj_id);
        if ($refs = $objectSettings->getDuplicatesOnSystem()) {
            $info->addSection(self::plugin()->translate('info_linked_items'));
            $i = 1;
            foreach ($refs as $ref) {
                $parent = $this->ilias_dic->repositoryTree()->getParentId($ref);
                $info->addProperty(($i) . '. '
                    . self::plugin()->translate('info_linked_item'), ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)), ilLink::_getStaticLink($parent));
                $i++;
            }
        }

        if ($objectSettings->getVideoPortalLink()
            && $this->opencast_dic->series_repository()->find($objectSettings->getSeriesIdentifier())->isPublishedOnVideoPortal()) {
            $info->addSection(self::plugin()->translate('series_links'));
            $info->addProperty(self::plugin()->translate('series_video_portal_link', '', [xoctConf::getConfig(xoctConf::F_VIDEO_PORTAL_TITLE)]), $objectSettings->getVideoPortalLink());
        }

        // general information
        $this->ilias_dic->language()->loadLanguageModule("meta");

        $this->addInfoItems($info);

        // forward the command
        $this->ilias_dic->ctrl()->forwardCommand($info);
    }


    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     *
     * @param bool $a_error
     */
    public function deleteObject($a_error = false)
    {
        if ($_GET["item_ref_id"] != "") {
            $_POST["id"] = array($_GET["item_ref_id"]);
        }

        if (is_array($_POST["id"])) {
            foreach ($_POST["id"] as $idx => $id) {
                $_POST["id"][$idx] = (int)$id;
            }
        }

        // SAVE POST VALUES (get rid of this
        ilSession::set("saved_post", $_POST["id"]);

        if (!$this->showDeleteConfirmation($_POST["id"], $a_error)) {
            $this->ilias_dic->ctrl()->returnToParent($this);
        }
    }


    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     */
    function showDeleteConfirmation($a_ids, $a_supress_message = false)
    {
        if (!is_array($a_ids) || count($a_ids) == 0) {
            ilUtil::sendFailure($this->ilias_dic->language()->txt("no_checkbox"), true);

            return false;
        }

        // Remove duplicate entries
        $a_ids = array_unique((array)$a_ids);

        include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
        $cgui = new ilConfirmationGUI();

        if (!$a_supress_message) {
            $msg = $this->ilias_dic->language()->txt("info_delete_sure");

            if (!$this->ilias_dic->settings()->get('enable_trash')) {
                $msg .= "<br/>" . $this->ilias_dic->language()->txt("info_delete_warning_no_trash");
            }

            $cgui->setHeaderText($msg);
        }
        $cgui->setFormAction($this->ilias_dic->ctrl()->getFormAction($this));
        $cgui->setCancel($this->ilias_dic->language()->txt("cancel"), "cancelDelete");
        $cgui->setConfirm($this->ilias_dic->language()->txt("confirm"), "confirmedDelete");

        $form_name = "cgui_" . md5(uniqid());
        $cgui->setFormName($form_name);

        $deps = array();
        foreach ($a_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);
            $title = call_user_func(array(ilObjectFactory::getClassByType($type), '_lookupTitle'), $obj_id);
            $alt = $this->ilias_dic->language()->txt("icon") . " " . ilPlugin::lookupTxt("rep_robj", $type, "obj_" . $type);

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

        $this->ilias_dic->ui()->mainTemplate()->setContent($cgui->getHTML() . $deps_html);

        return true;
    }

    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     * @param int    $a_obj_id
     * @param int    $a_ref_id
     * @param string $a_form_name
     * @return string
     * @throws Exception
     */
    function handleMultiReferences($a_obj_id, $a_ref_id, $a_form_name)
    {
        // process

        /** @var ObjectSettings $objectSettings */
        $objectSettings = ObjectSettings::find($a_obj_id);
        if ($all_refs = $objectSettings->getDuplicatesOnSystem()) {
            $this->ilias_dic->language()->loadLanguageModule("rep");

            $may_delete_any = 0;
            $counter = 0;
            $items = array();
            foreach ($all_refs as $mref_id) {
                // not the already selected reference, no refs from trash
                if ($mref_id != $a_ref_id && !$this->ilias_dic->repositoryTree()->isDeleted($mref_id)) {
                    if ($this->ilias_dic->access()->checkAccess("read", "", $mref_id)) {
                        $may_delete = false;
                        if ($this->ilias_dic->access()->checkAccess("delete", "", $mref_id)) {
                            $may_delete = true;
                            $may_delete_any++;
                        }

                        $path = $this->buildPath(array($mref_id));
                        $items[] = array(
                            "id" => $mref_id,
                            "path" => array_shift($path),
                            "delete" => $may_delete
                        );
                    } else {
                        $counter++;
                    }
                }
            }

            // render

            $tpl = new ilTemplate("tpl.rep_multi_ref.html", true, true, "Services/Repository");

            $tpl->setVariable("TXT_INTRO", $this->ilias_dic->language()->txt("rep_multiple_reference_deletion_intro"));

            if ($may_delete_any) {
                $tpl->setVariable("TXT_INSTRUCTION", $this->ilias_dic->language()->txt("rep_multiple_reference_deletion_instruction"));
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
                        $tpl->setVariable("TXT_ITEM_INFO", $this->ilias_dic->language()->txt("rep_no_permission_to_delete"));
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
                    $tpl->setVariable("ITEM_TITLE", $this->ilias_dic->language()->txt("select_all"));
                    $tpl->parseCurrentBlock();
                }
            }

            if ($counter) {
                $tpl->setCurrentBlock("add_info");
                $tpl->setVariable("TXT_ADDITIONAL_INFO", sprintf($this->ilias_dic->language()->txt("rep_object_references_cannot_be_read"), $counter));
                $tpl->parseCurrentBlock();
            }

            return $tpl->get();
        }
    }


    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     *
     * @param array $ref_ids
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
            $path_full = $this->ilias_dic->repositoryTree()->getPathFull($ref_id);
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
