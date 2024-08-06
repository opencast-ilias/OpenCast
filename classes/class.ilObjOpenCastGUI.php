<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use ILIAS\DI\Container;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use srag\Plugins\Opencast\DI\OpencastDIC;
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
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Container\Init;
use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\Container\Container as PluginContainer;

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
    use ilObjShowDuplicates; // handles the recognition of duplicates and shows them in delete confirmation

    public const CMD_SHOW_CONTENT = 'showContent';
    public const CMD_REDIRECT_SETTING = 'redirectSettings';
    public const TAB_EVENTS = 'series';
    public const TAB_SETTINGS = 'settings';
    public const TAB_INFO = 'info_short';
    public const TAB_GROUPS = 'groups';
    public const TAB_EULA = "eula";
    private ?array $form_data = null;

    protected Services $http;
    protected ?ilPropertyFormGUI $form = null;

    private OpencastDIC $legacy_container;
    private Container $ilias_dic;
    private PluginContainer $container;

    public function __construct(int $a_ref_id = 0, int $a_id_type = self::REPOSITORY_NODE_ID, int $a_parent_node_id = 0)
    {
        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
        $this->container = Init::init();
        $this->http = $this->container->ilias()->http();
    }

    protected function afterConstructor(): void
    {
        global $DIC;
        $this->ilias_dic = $DIC;
        $this->legacy_container = Init::init($DIC)->legacy();
        $this->plugin = $this->legacy_container->plugin();
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
            $this->ilias_dic->ui()->mainTemplate()->loadStandardTemplate();

            switch (strtolower((string) $next_class)) {
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
                        $this->legacy_container->acl_utils()
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
                        $this->legacy_container->acl_utils()
                    );
                    $this->ilias_dic->ctrl()->forwardCommand($xoctChangeOwnerGUI);
                    $this->showMainTemplate();
                    break;
                case strtolower(xoctSeriesGUI::class):
                    $objectSettings = $this->initHeader();
                    $this->setTabs();
                    $xoctSeriesGUI = new xoctSeriesGUI(
                        $this,
                        $this->object,
                        $this->legacy_container->series_form_builder(),
                        $this->container->get(SeriesAPIRepository::class),
                        $this->legacy_container->workflow_parameter_series_repository(),
                        $this->legacy_container->workflow_parameter_conf_repository()
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
                        $this->legacy_container->event_form_builder(),
                        $this->legacy_container->event_table_builder(),
                        $this->legacy_container->workflow_repository(),
                        $this->legacy_container->acl_utils(),
                        $this->container->get(SeriesAPIRepository::class),
                        $this->legacy_container->upload_handler(),
                        $this->legacy_container->paella_config_storage_service(),
                        $this->legacy_container->paella_config_service_factory(),
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
            throw $e; // DEBUG
            $this->tpl->setOnScreenMessage('failure', $e->getMessage());
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
        $this->ilias_dic->ui()->mainTemplate()->printToStdout();
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

    protected function setTabs(): void
    {
        /**
         * @var $objectSettings ObjectSettings
         */
        $objectSettings = ObjectSettings::find($this->obj_id);
        if (!$objectSettings instanceof ObjectSettings) {
            return;
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
                    static::class,
                    "ilpermissiongui",
                ], "perm")
            );
        }

        if (ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_UPLOAD)) {
            $this->ilias_dic->tabs()->addTab(
                self::TAB_EULA,
                $this->plugin->txt("eula"),
                $this->ilias_dic->ctrl()->getLinkTarget($this, "showEula")
            );
        }
    }

    private function showEula(): void
    {
        $this->tabs_gui->activateTab("eula");
        $this->tpl->setContent(PluginConfig::getConfig(PluginConfig::F_EULA));
    }

    /**
     * @param string $a_new_type
     * @throws arException
     * @throws xoctException
     */
    protected function initCreationForms($a_new_type): array
    {
        global $DIC;
        $ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
        if (!ilObjOpenCast::_getParentCourseOrGroup($ref_id)) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('msg_creation_failed'), true);
            ilUtil::redirect('/');
        }
        $this->ilias_dic->ctrl()->setParameter($this, 'new_type', ilOpenCastPlugin::PLUGIN_ID);

        return [self::CFORM_NEW => $this->initCreateForm($a_new_type)];
    }

    /**
     * @param string     $type
     * @param bool|false $from_post
     */
    public function initCreateForm($type, $from_post = false): LegacyFormWrapper
    {
        return new LegacyFormWrapper(
            $this->ilias_dic->ui()->renderer()->render(
                $this->buildUIForm()
            )
        );
    }

    private function buildUIForm(): Form
    {
        return $this->legacy_container->series_form_builder()->create(
            $this->ilias_dic->ctrl()->getFormAction($this, 'save')
        );
    }

    /**
     * @throws arException
     * @throws xoctException
     */
    public function save(): void
    {
        $creation_form = $this->buildUIForm()->withRequest($this->ilias_dic->http()->request());
        $this->form_data = $creation_form->getData();

        if (!$this->form_data) {
            $this->ilias_dic->ui()->mainTemplate()->setContent(
                $this->ilias_dic->ui()->renderer()->render($creation_form)
            );
            return;
        }

        $this->saveObject();
    }

    /**
     * @throws xoctException
     */
    public function afterSave(ilObject $newObj): void
    {
        global $DIC;
        /**
         * @var $settings ObjectSettings
         */

        $additional_args = $this->form_data;
        if ($additional_args !== null) {
            /** @var ObjectSettings $settings */
            $settings = $additional_args['settings']['object'] ?? null;
            /** @var Metadata $metadata */
            $metadata = $additional_args['series_type']['metadata'] ?? null;
            /** @var string|false $series_id */
            $series_id = $additional_args['series_type']['channel_id'] ?? null;
            /** @var bool $is_memberupload_enabled */
            $is_memberupload_enabled = (bool) ($additional_args['member_rights']['member_upload'] ?? false);
            /** @var bool $is_memberdownload_enabled */
            $is_memberdownload_enabled = (bool) ($additional_args['member_rights']['member_download'] ?? false);
            /** @var bool $is_memberrecord_enabled */
            $is_memberrecord_enabled = (bool) ($additional_args['member_rights']['member_record'] ?? false);
            /** @var int $perm_tpl_id */
            $perm_tpl_id = $additional_args['settings']['permission_template'] ?? null;
        }

        // set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
        $producers = ilObjOpenCastAccess::getProducersForRefID((int) $newObj->getRefId());
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

        $acl = $this->legacy_container->acl_utils()->getStandardRolesACL();
        foreach ($producers as $producer) {
            $acl->merge($this->legacy_container->acl_utils()->getUserRolesACL($producer));
        }

        if (empty($perm_tpl_id)) {
            $perm_tpl = PermissionTemplate::where(['is_default' => 1])->first();
        } else {
            $acl = PermissionTemplate::removeAllTemplatesFromAcls($acl);
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find($perm_tpl_id);
        }
        if (!empty($perm_tpl)) {
            $acl = $perm_tpl->addToAcls(
                $acl,
                $settings->getUseAnnotations()
            );
        }
        // TODO: do we need contributor / organizer?
        if (empty($series_id)) {
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
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('msg_info_multiple_aftersave'), true);
        }

        // Initiate the default perms for members.
        // Looking for additionals when creating a new series object.
        $additional_perms = [];
        if (!empty($is_memberupload_enabled)) {
            $additional_perms[ilObjOpenCastAccess::ROLE_MEMBER][] = ilObjOpenCastAccess::PERMISSION_UPLOAD;
        }
        if (!empty($is_memberdownload_enabled)) {
            $additional_perms[ilObjOpenCastAccess::ROLE_MEMBER][] = ilObjOpenCastAccess::PERMISSION_DOWNLOAD;
        }
        if (!empty($is_memberrecord_enabled)) {
            $additional_perms[ilObjOpenCastAccess::ROLE_MEMBER][] = ilObjOpenCastAccess::PERMISSION_RECORD;
        }
        ilObjOpenCastAccess::applyDefaultPerms($newObj->getRefId(), $additional_perms);

        $newObj->setTitle((string) $metadata->getField(MDFieldDefinition::F_TITLE)->getValue());
        $newObj->setDescription((string) ($metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue() ?? ''));
        $newObj->update();

        $this->legacy_container->workflow_parameter_series_repository()->syncAvailableParameters($newObj->getId());

        parent::afterSave($newObj);
    }

    /**
     * @param bool $render_locator
     *
     * @return ObjectSettings
     * @throws xoctException
     */
    protected function initHeader($render_locator = true): ?\ActiveRecord
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

        $ref_id = $this->ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        if ($objectSettings instanceof ObjectSettings && $this->object) {
            $this->ilias_dic->ui()->mainTemplate()->setTitle($this->object->getTitle());
            $this->ilias_dic->ui()->mainTemplate()->setDescription($this->object->getDescription());

            if ($this->ilias_dic->access()->checkAccess('read', '', $ref_id)) {
                $DIC['ilNavigationHistory']->addItem(
                    $ref_id,
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
        $this->ilias_dic->ui()->mainTemplate()->setPermanentLink(ilOpenCastPlugin::PLUGIN_ID, $ref_id);

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
        /** @var $objectSettings ObjectSettings */
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
            $video_portal_title = PluginConfig::getConfig(PluginConfig::F_VIDEO_PORTAL_TITLE);
            $info->addSection($this->plugin->txt('series_links'));
            $info->addProperty(
                sprintf(
                    $this->plugin->txt(
                        'series_video_portal_link'
                    ),
                    $video_portal_title
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
     * Checks the series duplicates and renders a list of linked series.
     */
    public function renderLinksListSection(): string
    {
        $objectSettings = ObjectSettings::find($this->obj_id);
        if ($refs = $objectSettings->getDuplicatesOnSystem()) {
            $links_list_tpl = $this->plugin->getTemplate('default/tpl.links_list.html');
            $links_list_tpl->setVariable('TXT_SECTION', $this->plugin->txt('info_linked_items'));
            $i = 1;
            $list_items = [];
            foreach ($refs as $ref) {
                $links_list_item_tpl = $this->plugin->getTemplate('default/tpl.links_list_item.html');
                $parent = $this->ilias_dic->repositoryTree()->getParentId($ref);
                $links_list_item_tpl->setVariable('TXT_KEY', ($i) . '. ' . $this->plugin->txt('info_linked_item'));
                $links_list_item_tpl->setVariable('TXT_LINK', ilLink::_getStaticLink($parent));
                $links_list_item_tpl->setVariable('TXT_LINK_LABEL', ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)));
                $list_items[] = $links_list_item_tpl->get();
                $i++;
            }
            $links_list_tpl->setVariable('LIST_ITEMS', implode(' ', $list_items));
            return $links_list_tpl->get();
        }
        return '';
    }
}
