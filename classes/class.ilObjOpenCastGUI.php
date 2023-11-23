<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ILIAS\DI\Container;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Cache\Service\DB\DBCacheService;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Group\Group;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequestPayload;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\UI\LegacyFormWrapper;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;

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
    /**
     * @var mixed
     */
    public $creation_mode;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const CMD_SHOW_CONTENT = 'showContent';
    public const CMD_REDIRECT_SETTING = 'redirectSettings';
    public const TAB_EVENTS = 'series';
    public const TAB_SETTINGS = 'settings';
    public const TAB_INFO = 'info_short';
    public const TAB_GROUPS = 'groups';
    public const TAB_EULA = "eula";

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
    /**
     * @var \srag\Plugins\Opencast\Container\Container
     */
    private $container;

    public function __construct($a_ref_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
        global $opencastContainer;
        $this->container = $opencastContainer;
    }

    protected function afterConstructor()
    {
        global $DIC;
        $this->ilias_dic = $DIC;
        $this->opencast_dic = OpencastDIC::getInstance();
        $this->plugin = $this->opencast_dic->plugin();
    }

    final public function getType(): string
    {
        return ilOpenCastPlugin::PLUGIN_ID;
    }

    /**
     * @param $cmd
     */
    public function performCommand($cmd): void
    {
        $this->{$cmd}();
    }

    public function executeCommand(): void
    {
        $this->checkPermission('read');
        try {
            PluginConfig::setApiSettings();
            $next_class = $this->ilias_dic->ctrl()->getNextClass();
            $cmd = $this->ilias_dic->ctrl()->getCmd();
            if (xoct::isIlias6()) {
                $this->ilias_dic->ui()->mainTemplate()->loadStandardTemplate();
            } else {
                $this->ilias_dic->ui()->mainTemplate()->getStandardTemplate();
            }

            switch (strtolower($next_class)) {
                case strtolower(xoctPermissionGroupParticipantGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctPermissionGroupParticipantGUI = new xoctPermissionGroupParticipantGUI($objectSettings);
                    $this->ilias_dic->ctrl()->forwardCommand($xoctPermissionGroupParticipantGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctGrantPermissionGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctGrantPermissionGUI = new xoctGrantPermissionGUI(
                        $objectSettings,
                        $this->container[EventAPIRepository::class],
                        $this->opencast_dic->acl_utils()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctGrantPermissionGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctChangeOwnerGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctChangeOwnerGUI = new xoctChangeOwnerGUI(
                        $objectSettings,
                        $this->container[EventAPIRepository::class],
                        $this->opencast_dic->acl_utils()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctChangeOwnerGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctSeriesGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctSeriesGUI(
                        $this->object,
                        $this->opencast_dic->series_form_builder(),
                        $this->container->get(SeriesAPIRepository::class),
                        $this->opencast_dic->workflow_parameter_series_repository(),
                        $this->opencast_dic->workflow_parameter_conf_repository()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctSeriesGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctEventGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctEventGUI = new xoctEventGUI(
                        $this,
                        $objectSettings,
                        $this->container[EventAPIRepository::class],
                        $this->opencast_dic->event_form_builder(),
                        $this->opencast_dic->event_table_builder(),
                        $this->opencast_dic->workflow_repository(),
                        $this->opencast_dic->acl_utils(),
                        $this->container->get(SeriesAPIRepository::class),
                        $this->opencast_dic->upload_handler(),
                        $this->opencast_dic->paella_config_storage_service(),
                        $this->opencast_dic->paella_config_service_factory(),
                        $this->ilias_dic
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctEventGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctPermissionGroupGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctPermissionGroupGUI = new xoctPermissionGroupGUI($objectSettings);
                    $this->ilias_dic->ctrl()->forwardCommand($xoctPermissionGroupGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(ilPermissionGUI::class):
                    $this->initHeader(false);
                    parent::executeCommand();
                    break;
                default:
                    $this->ilias_dic->ctrl()->saveParameter($this, 'new_type');
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
    }

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

    public function getAfterCreationCmd(): string
    {
        return self::CMD_SHOW_CONTENT;
    }

    public function getStandardCmd(): string
    {
        return self::CMD_SHOW_CONTENT;
    }

    /**
     * @throws DICException
     */
    protected function setTabs(): bool
    {
        /**
         * @var $objectSettings ObjectSettings
         */
        $objectSettings = ObjectSettings::find($this->obj_id);
        if (!$objectSettings instanceof ObjectSettings) {
            return false;
        }

        $this->ilias_dic->tabs()->addTab(
            self::TAB_EVENTS,
            $this->plugin->txt('tab_event_index'),
            $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_STANDARD)
        );
        $this->ilias_dic->tabs()->addTab(
            self::TAB_INFO,
            $this->plugin->txt('tab_info'),
            $this->ilias_dic->ctrl()->getLinkTarget($this, 'infoScreen')
        );

        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_SETTINGS)) {
            $this->ilias_dic->tabs()->addTab(
                self::TAB_SETTINGS,
                $this->plugin->txt('tab_series_settings'),
                $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctSeriesGUI::class, xoctSeriesGUI::CMD_EDIT_GENERAL)
            );
        }

        if ($objectSettings->getPermissionPerClip() && ilObjOpenCastAccess::hasPermission('read')) {
            $this->ilias_dic->tabs()->addTab(
                self::TAB_GROUPS,
                $this->plugin->txt('tab_groups'),
                $this->ilias_dic->ctrl()->getLinkTarget(new xoctPermissionGroupGUI())
            );
        }
        if ($this->ilias_dic->user()->getId() == 6 && ilObjOpenCast::DEV) {
            $this->ilias_dic->tabs()->addTab(
                'migrate_event',
                $this->plugin->txt('tab_migrate_event'),
                $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'search')
            );
            $this->ilias_dic->tabs()->addTab(
                'list_all',
                $this->plugin->txt('tab_list_all'),
                $this->ilias_dic->ctrl()->getLinkTargetByClass(xoctEventGUI::class, 'listAll')
            );
        }

        if ($this->checkPermissionBool("edit_permission")) {
            $this->ilias_dic->tabs()->addTab(
                "perm_settings",
                $this->ilias_dic->language()->txt("perm_settings"),
                $this->ilias_dic->ctrl()->getLinkTargetByClass([
                    get_class($this),
                    "ilpermissiongui",
                ], "perm")
            );
        }

        // ToDo: Why does this access check not work?
        if (ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_UPLOAD)) {
            $this->ilias_dic->tabs()->addTab(
                self::TAB_EULA,
                $this->plugin->txt("eula"),
                $this->ilias_dic->ctrl()->getLinkTarget($this, "showEula")
            );
        }
        return true;
    }

    private function showEula(): void
    {
        $this->tabs_gui->activateTab("eula");
        $this->tpl->setContent(PluginConfig::getConfig(PluginConfig::F_EULA));
    }

    /**
     * @param string $a_new_type
     * @throws DICException
     * @throws arException
     * @throws xoctException
     */
    protected function initCreationForms($a_new_type): array
    {
        if (!ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id'])) {
            ilUtil::sendFailure($this->plugin->txt('msg_creation_failed'), true);
            ilUtil::redirect('/');
        }
        $this->ilias_dic->ctrl()->setParameter($this, 'new_type', ilOpenCastPlugin::PLUGIN_ID);

        return [self::CFORM_NEW => $this->initCreateForm($a_new_type)];
    }

    /**
     * @param string     $type
     * @param bool|false $from_post
     */
    public function initCreateForm($type, $from_post = false): \srag\Plugins\Opencast\UI\LegacyFormWrapper
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
    public function save(): void
    {
        $creation_form = $this->buildUIForm()->withRequest($this->ilias_dic->http()->request());
        $data = $creation_form->getData();

        if (!$data) {
            $this->ilias_dic->ui()->mainTemplate()->setContent(
                $this->ilias_dic->ui()->renderer()->render($creation_form)
            );
            return;
        }

        $this->saveObject($data);
    }

    /**
     * @throws DICException
     * @throws xoctException
     */
    public function afterSave(ilObject $newObj): void
    {
        global $DIC;
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
        $is_memberupload_enabled = (bool) ($additional_args['settings']['member_upload'] ?? false);
        /** @var int $perm_tpl_id */
        $perm_tpl_id = $additional_args['settings']['permission_template'];

        // set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
        $producers = ilObjOpenCastAccess::getProducersForRefID($newObj->getRefId());
        $producers[] = xoctUser::getInstance($this->ilias_dic->user());

        try {
            if ($group_producers = PluginConfig::getConfig(PluginConfig::F_GROUP_PRODUCERS)) {
                $ilias_producers = Group::find($group_producers);
                $ilias_producers->addMembers($producers);
            }
        } catch (xoctException $e) {
            $DIC->logger()->root()->warning(
                'Could not add producers to group while creating a series, msg: '
                . $e->getMessage()
            );
        }

        $acl = $this->opencast_dic->acl_utils()->getStandardRolesACL();
        foreach ($producers as $producer) {
            $acl->merge($this->opencast_dic->acl_utils()->getUserRolesACL($producer));
        }

        if ($perm_tpl_id == '') {
            $perm_tpl = PermissionTemplate::where(['is_default' => 1])->first();
        } else {
            $acl = PermissionTemplate::removeAllTemplatesFromAcls($acl);
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find($perm_tpl_id);
        }
        if ($perm_tpl) {
            $acl = $perm_tpl->addToAcls(
                $acl,
                !$settings->getStreamingOnly(),
                $settings->getUseAnnotations()
            );
        }
        // TODO: do we need contributor / organizer?
        if (!$series_id) {
            $series_id = $this->container->get(SeriesAPIRepository::class)->create(
                new CreateSeriesRequest(
                    new CreateSeriesRequestPayload(
                        $metadata,
                        $acl
                    )
                )
            );
        } else {
            $metadata = $this->container->get(SeriesAPIRepository::class)->find($series_id)->getMetadata();
        }

        if ($series_id !== null) {
            $settings->setSeriesIdentifier($series_id);
        }
        $settings->setObjId($newObj->getId());
        $settings->create();

        if ($settings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('msg_info_multiple_aftersave'), true);
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
        global $DIC;
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
                $DIC['ilNavigationHistory']->addItem(
                    $_GET['ref_id'],
                    $this->ilias_dic->ctrl()->getLinkTarget($this, $this->getStandardCmd()),
                    $this->getType(),
                    $this->object->getTitle()
                );
            }
            $list_gui = ilObjectListGUIFactory::_getListGUIByType(ilOpenCastPlugin::PLUGIN_ID);
            /**
             * @var $list_gui ilObjOpenCastListGUI
             */
            if (!$objectSettings->isOnline()) {
                $this->ilias_dic->ui()->mainTemplate()->setAlertProperties($list_gui->getAlertProperties());
            }
        } else {
            $this->ilias_dic->ui()->mainTemplate()->setTitle($this->plugin->txt('series_create'));
        }
        $this->ilias_dic->ui()->mainTemplate()->setTitleIcon(ilObjOpenCast::_getIcon($this->object_id));
        $this->ilias_dic->ui()->mainTemplate()->setPermanentLink(ilOpenCastPlugin::PLUGIN_ID, $_GET['ref_id']);

        return $objectSettings;
    }

    /**
     * show information screen
     */
    public function infoScreen(): void
    {
        /**
         * @var $objectSettings ObjectSettings
         * @var $item           ObjectSettings
         * @var $tree           ilTree
         */
        $this->ilias_dic->tabs()->setTabActive("info_short");
        $this->initHeader(false);
        $this->checkPermission("visible");

        $info = new ilInfoScreenGUI($this);
        $info->enablePrivateNotes();
        $objectSettings = ObjectSettings::find($this->obj_id);
        if ($refs = $objectSettings->getDuplicatesOnSystem()) {
            $info->addSection($this->plugin->txt('info_linked_items'));
            $i = 1;
            foreach ($refs as $ref) {
                $parent = $this->ilias_dic->repositoryTree()->getParentId($ref);
                $info->addProperty(
                    ($i) . '. '
                    . $this->plugin->txt('info_linked_item'),
                    ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)),
                    ilLink::_getStaticLink($parent)
                );
                $i++;
            }
        }

        if ($objectSettings->getVideoPortalLink()
            && $this->container->get(SeriesAPIRepository::class)->find(
                $objectSettings->getSeriesIdentifier()
            )->isPublishedOnVideoPortal()) {
            $info->addSection($this->plugin->txt('series_links'));
            $info->addProperty(
                $this->plugin->txt(
                    'series_video_portal_link'
                ),
                $objectSettings->getVideoPortalLink()
            );
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
    public function deleteObject($a_error = false): void
    {
        if ($_GET["item_ref_id"] != "") {
            $_POST["id"] = [$_GET["item_ref_id"]];
        }

        if (is_array($_POST["id"])) {
            foreach ($_POST["id"] as $idx => $id) {
                $_POST["id"][$idx] = (int) $id;
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
    public function showDeleteConfirmation($a_ids, $a_supress_message = false): bool
    {
        if (!is_array($a_ids) || count($a_ids) == 0) {
            ilUtil::sendFailure($this->ilias_dic->language()->txt("no_checkbox"), true);

            return false;
        }

        // Remove duplicate entries
        $a_ids = array_unique($a_ids);

        $cgui = new ilConfirmationGUI();

        if (!$a_supress_message) {
            $msg = $this->ilias_dic->language()->txt("info_delete_sure");

            if ($this->ilias_dic->settings()->get('enable_trash') === '' || $this->ilias_dic->settings()->get(
                'enable_trash'
            ) === '0') {
                $msg .= "<br/>" . $this->ilias_dic->language()->txt("info_delete_warning_no_trash");
            }

            $cgui->setHeaderText($msg);
        }
        $cgui->setFormAction($this->ilias_dic->ctrl()->getFormAction($this));
        $cgui->setCancel($this->ilias_dic->language()->txt("cancel"), "cancelDelete");
        $cgui->setConfirm($this->ilias_dic->language()->txt("confirm"), "confirmedDelete");

        $form_name = "cgui_" . md5(uniqid(''));
        $cgui->setFormName($form_name);

        $deps = [];
        foreach ($a_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);
            $title = call_user_func([ilObjectFactory::getClassByType($type), '_lookupTitle'], $obj_id);
            $alt = $this->ilias_dic->language()->txt("icon") . " " . ilPlugin::lookupTxt(
                "rep_robj",
                $type,
                "obj_" . $type
            );

            $title .= $this->handleMultiReferences($obj_id, $ref_id, $form_name);

            $cgui->addItem("id[]", $ref_id, $title, ilObject::_getIcon($obj_id, "small", $type), $alt);

            ilObject::collectDeletionDependencies($deps, $ref_id, $obj_id, $type);
        }
        $deps_html = "";

        if (is_array($deps) && $deps !== []) {
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
    public function handleMultiReferences($a_obj_id, $a_ref_id, $a_form_name)
    {
        // process

        /** @var ObjectSettings $objectSettings */
        $objectSettings = ObjectSettings::find($a_obj_id);
        if ($all_refs = $objectSettings->getDuplicatesOnSystem()) {
            $this->ilias_dic->language()->loadLanguageModule("rep");

            $may_delete_any = 0;
            $counter = 0;
            $items = [];
            foreach ($all_refs as $mref_id) {
                // not the already selected reference, no refs from trash
                if ($mref_id != $a_ref_id && !$this->ilias_dic->repositoryTree()->isDeleted($mref_id)) {
                    if ($this->ilias_dic->access()->checkAccess("read", "", $mref_id)) {
                        $may_delete = false;
                        if ($this->ilias_dic->access()->checkAccess("delete", "", $mref_id)) {
                            $may_delete = true;
                            $may_delete_any++;
                        }

                        $path = $this->buildPath([$mref_id]);
                        $items[] = [
                            "id" => $mref_id,
                            "path" => array_shift($path),
                            "delete" => $may_delete
                        ];
                    } else {
                        $counter++;
                    }
                }
            }

            // render

            $tpl = new ilTemplate("tpl.rep_multi_ref.html", true, true, "Services/Repository");

            $tpl->setVariable("TXT_INTRO", $this->ilias_dic->language()->txt("rep_multiple_reference_deletion_intro"));

            if ($may_delete_any !== 0) {
                $tpl->setVariable(
                    "TXT_INSTRUCTION",
                    $this->ilias_dic->language()->txt("rep_multiple_reference_deletion_instruction")
                );
            }

            if ($items !== []) {
                $var_name = "mref_id[]";

                foreach ($items as $item) {
                    if ($item["delete"]) {
                        $tpl->setCurrentBlock("cbox");
                        $tpl->setVariable("ITEM_NAME", $var_name);
                        $tpl->setVariable("ITEM_VALUE", $item["id"]);
                        $tpl->parseCurrentBlock();
                    } else {
                        $tpl->setCurrentBlock("item_info");
                        $tpl->setVariable(
                            "TXT_ITEM_INFO",
                            $this->ilias_dic->language()->txt("rep_no_permission_to_delete")
                        );
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
                    $tpl->setVariable(
                        "ITEM_ADD",
                        " onclick=\"il.Util.setChecked('" . $a_form_name . "', '" . $var_name . "', document."
                        . $a_form_name . ".sall_" . $a_ref_id . ".checked)\""
                    );
                    $tpl->parseCurrentBlock();

                    $tpl->setCurrentBlock("item");
                    $tpl->setVariable("ITEM_TITLE", $this->ilias_dic->language()->txt("select_all"));
                    $tpl->parseCurrentBlock();
                }
            }

            if ($counter !== 0) {
                $tpl->setCurrentBlock("add_info");
                $tpl->setVariable(
                    "TXT_ADDITIONAL_INFO",
                    sprintf($this->ilias_dic->language()->txt("rep_object_references_cannot_be_read"), $counter)
                );
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
        if ($ref_ids === []) {
            return false;
        }

        $result = [];
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
                    $path .= ('<a target="_top" href="' . ilLink::_getLink(
                        $data['ref_id'],
                        $data['type']
                    ) . '">' . $data['title'] . '</a>');
                }
            }

            $result[] = $path;
        }

        return $result;
    }
}
