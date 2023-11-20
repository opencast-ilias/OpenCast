<?php

use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Cache\CacheFactory;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\ScheduleEventRequestPayload;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequestPayload;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequestPayload;
use srag\Plugins\Opencast\Model\Group\Group;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Model\Report\Report;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequestPayload;
use srag\Plugins\Opencast\Model\Series\SeriesRepository;
use srag\Plugins\Opencast\Model\TermsOfUse\ToUManager;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Model\UserSettings\UserSettingsRepository;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;
use srag\Plugins\Opencast\UI\EventFormBuilder;
use srag\Plugins\Opencast\UI\EventTableBuilder;
use srag\Plugins\Opencast\UI\Modal\EventModals;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use srag\Plugins\Opencast\Util\Player\PaellaConfigServiceFactory;
use srag\Plugins\OpenCast\UI\Component\Input\Field\Loader;
use srag\CustomInputGUIs\OneDrive\Waiter\Waiter;
use srag\Plugins\Opencast\API\OpencastAPI;

/**
 * Class xoctEventGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_Calls      xoctEventGUI: xoctPlayerGUI
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI
{
    public const IDENTIFIER = 'eid';
    public const CMD_STANDARD = 'index';
    public const CMD_CLEAR_CACHE = 'clearCache';
    public const CMD_EDIT_OWNER = 'editOwner';
    public const CMD_UPDATE_OWNER = 'updateOwner';
    public const CMD_SET_ONLINE = 'setOnline';
    public const CMD_SET_OFFLINE = 'setOffline';
    public const CMD_CUT = 'cut';
    public const CMD_ANNOTATE = 'annotate';
    public const CMD_REPORT_DATE = 'reportDate';
    public const CMD_REPORT_QUALITY = 'reportQuality';
    public const CMD_SCHEDULE = 'schedule';
    public const CMD_SWITCH_TO_LIST = 'switchToList';
    public const CMD_SWITCH_TO_TILES = 'switchToTiles';
    public const CMD_CHANGE_TILE_LIMIT = 'changeTileLimit';
    public const CMD_REPUBLISH = 'republish';
    public const CMD_OPENCAST_STUDIO = 'opencaststudio';
    public const CMD_DOWNLOAD = 'download';
    public const CMD_CREATE_SCHEDULED = 'createScheduled';
    public const CMD_EDIT_SCHEDULED = 'editScheduled';
    public const CMD_UPDATE_SCHEDULED = 'updateScheduled';
    /**
     * @var ilObjOpenCastGUI
     */
    private $parent_gui;
    /**
     * @var \ILIAS\UI\Implementation\DefaultRenderer
     */
    protected $custom_renderer;

    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var EventModals
     */
    protected $modals;
    /**
     * @var EventRepository
     */
    protected $event_repository;
    /**
     * @var Renderer
     */
    private $ui_renderer;
    /**
     * @var EventFormBuilder
     */
    private $formBuilder;
    /**
     * @var WorkflowRepository
     */
    private $workflowRepository;
    /**
     * @var ACLUtils
     */
    private $ACLUtils;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var SeriesRepository
     */
    private $seriesRepository;
    /**
     * @var EventTableBuilder
     */
    private $eventTableBuilder;
    /**
     * @var xoctFileUploadHandler
     */
    private $uploadHandler;
    /**
     * @var PaellaConfigStorageService
     */
    private $paellaConfigStorageService;
    /**
     * @var PaellaConfigServiceFactory
     */
    private $paellaConfigServiceFactory;
    /**
     * @var \ilObjUser
     */
    private $user;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;
    /**
     * @var \ILIAS\HTTP\Services
     */
    private $http;

    public function __construct(
        ilObjOpenCastGUI $parent_gui,
        ObjectSettings $objectSettings,
        EventRepository $event_repository,
        EventFormBuilder $formBuilder,
        EventTableBuilder $eventTableBuilder,
        WorkflowRepository $workflowRepository,
        ACLUtils $ACLUtils,
        SeriesRepository $seriesRepository,
        UploadHandler $uploadHandler,
        PaellaConfigStorageService $paellaConfigStorageService,
        PaellaConfigServiceFactory $paellaConfigServiceFactory,
        Container $dic
    ) {
        global $DIC;
        parent::__construct();

        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
        $this->ui = $DIC->ui();
        $this->http = $DIC->http();
        $this->objectSettings = $objectSettings;
        $this->parent_gui = $parent_gui;
        $this->event_repository = $event_repository;
        $this->formBuilder = $formBuilder;
        $this->workflowRepository = $workflowRepository;
        $this->ACLUtils = $ACLUtils;
        $this->dic = $dic;
        $this->seriesRepository = $seriesRepository;
        $this->eventTableBuilder = $eventTableBuilder;
        $this->uploadHandler = $uploadHandler;
        $this->paellaConfigStorageService = $paellaConfigStorageService;
        $this->paellaConfigServiceFactory = $paellaConfigServiceFactory;
        $this->ui_renderer = new \ILIAS\UI\Implementation\DefaultRenderer(
            new Loader($DIC, ilOpenCastPlugin::getInstance())
        );
    }

    /**
     * @throws DICException
     * @throws ilCtrlException
     * @throws xoctException
     */
    public function executeCommand(): void
    {
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctPlayerGUI::class):
                $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER));
                // check access
                if (!ilObjOpenCastAccess::hasReadAccessOnEvent(
                    $event,
                    xoctUser::getInstance($this->user),
                    $this->objectSettings
                )) {
                    ilUtil::sendFailure($this->txt("msg_no_access"), true);
                    $this->cancel();
                }
                $xoctPlayerGUI = new xoctPlayerGUI(
                    $this->event_repository,
                    $this->paellaConfigStorageService,
                    $this->paellaConfigServiceFactory,
                    $this->objectSettings
                );
                $this->ctrl->forwardCommand($xoctPlayerGUI);
                break;
            case strtolower(xoctFileUploadHandler::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    ilUtil::sendFailure($this->txt("msg_no_access"), true);
                    $this->cancel();
                }
                $this->ctrl->forwardCommand($this->uploadHandler);
                break;
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
        $this->main_tpl->addCss(
            './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.css'
        );
        $this->main_tpl->addJavaScript(
            './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.js'
        );    // init waiter
        $this->main_tpl->addOnLoadCode(
            "$(document).on('shown.bs.dropdown', (e) => {
					$(e.target).children('.dropdown-menu').each((i, el) => {
						il.Util.fixPosition(el);
					});
				});"
        );    // fix action menu position bug
        $this->main_tpl->addCss(
            $this->plugin->getDirectory() . '/templates/default/reporting_modal.css'
        );

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
    protected function prepareContent()
    {
        xoctWaiterGUI::initJS();
        xoctWaiterGUI::addLinkOverlay('#rep_robj_xoct_event_clear_cache');
        $this->main_tpl->addJavascript("./src/UI/templates/js/Modal/modal.js");
        $this->main_tpl->addOnLoadCode(
            'xoctEvent.init(\'' . json_encode([
                'msg_link_copied' => $this->plugin->txt('msg_link_copied'),
                'tooltip_copy_link' => $this->plugin->txt('tooltip_copy_link')
            ]) . '\');'
        );

        // add "add" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_add_new');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
            $b->setPrimary(true);
            $this->toolbar->addButtonInstance($b);
        }

        // add "schedule" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && PluginConfig::getConfig(
            PluginConfig::F_CREATE_SCHEDULED_ALLOWED
        )) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_schedule_new');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SCHEDULE));
            $b->setPrimary(true);
            $this->toolbar->addButtonInstance($b);
        }

        // add "Opencast Studio" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && PluginConfig::getConfig(
            PluginConfig::F_STUDIO_ALLOWED
        )) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_opencast_studio');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_OPENCAST_STUDIO));
            $b->setPrimary(true);
            $this->toolbar->addButtonInstance($b);
        }

        // add "clear cache" button
        if (PluginConfig::getConfig(PluginConfig::F_ACTIVATE_CACHE)) {
            $b = ilLinkButton::getInstance();
            $b->setId('rep_robj_xoct_event_clear_cache');
            $b->setCaption('rep_robj_xoct_event_clear_cache');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_CLEAR_CACHE));
            $this->toolbar->addButtonInstance($b);
        }

        // add "report date change" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
            $b = ilButton::getInstance();
            $b->setId('xoct_report_date_button');
            $b->setCaption('rep_robj_xoct_event_report_date_modification');
            $b->addCSSClass('hidden');

            $this->toolbar->addButtonInstance($b);
        }
    }

    /**
     * asynchronous loading of tableGUI
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function index()
    {
        $filter_html = null;
        ilChangeEvent::_recordReadEvent(
            $this->parent_gui->object->getType(),
            $this->parent_gui->object->getRefId(),
            $this->objectSettings->getObjId(),
            $this->user->getId()
        );

        switch (UserSettingsRepository::getViewTypeForUser($this->user->getId(), filter_input(INPUT_GET, 'ref_id'))) {
            case UserSettingsRepository::VIEW_TYPE_LIST:
                $html = $this->indexList();
                break;
            case UserSettingsRepository::VIEW_TYPE_TILES:
                $html = $this->indexTiles();
                break;
            default:
                throw new xoctException(
                    xoctException::INTERNAL_ERROR,
                    'Invalid view type ' .
                    UserSettingsRepository::getViewTypeForUser(
                        $this->user->getId(),
                        filter_input(INPUT_GET, 'ref_id')
                    ) .
                    ' for user with id ' . $this->user->getId()
                );
        }

        if (xoct::isIlias7()) { // todo: remove when this is fixed https://mantis.ilias.de/view.php?id=32134
            $filter_html = $this->dic->ui()->renderer()->render(
                $this->eventTableBuilder->filter(
                    $this->dic->ctrl()->getFormAction($this, self::CMD_STANDARD, '')
                )
            );
        }
        $intro_text = $this->createHyperlinks($this->getIntroTextHTML());
        $this->main_tpl->setContent($intro_text . $filter_html . $html);
    }

    /**
     * @return string
     * @throws DICException
     */
    protected function indexList()
    {
        $this->initViewSwitcherHTML('list');

        if (isset($_GET[xoctEventTableGUI::getGeneratedPrefix($this->getObjId()) . '_xpt'])
            || $_POST !== []
            || PluginConfig::getConfig(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY)) {
            // load table synchronously
            return $this->getTableGUI();
        }

        if (isset($_GET['async'])) {
            return $this->asyncGetTableGUI();
        }

        $this->main_tpl->addJavascript("./Services/Table/js/ServiceTable.js");
        $this->loadAjaxCodeForList();    // load table asynchronously
        return '<div id="xoct_table_placeholder"></div>';
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function indexTiles()
    {
        $this->initViewSwitcherHTML('tiles');

        if (PluginConfig::getConfig(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY)) {
            return $this->getTilesGUI();
        }

        if (isset($_GET['async'])) {
            return $this->asyncGetTilesGUI();
        }

        $this->loadAjaxCodeForTiles();    // load tiles asynchronously
        return '<div id="xoct_tiles_placeholder"></div>';
    }

    /**
     * @param $active
     * @return string
     * @throws DICException
     */
    protected function initViewSwitcherHTML($active)
    {
        if ($this->objectSettings->isViewChangeable()) {
            $f = $this->ui->factory();
            $renderer = $this->ui->renderer();

            $actions = [
                $this->plugin->txt('list') => $this->ctrl->getLinkTarget($this, self::CMD_SWITCH_TO_LIST),
                $this->plugin->txt('tiles') => $this->ctrl->getLinkTarget($this, self::CMD_SWITCH_TO_TILES),
            ];

            $aria_label = $this->plugin->txt('info_view_switcher');
            $view_control = $f->viewControl()->mode($actions, $aria_label)->withActive(
                $this->plugin->txt($active)
            );
            $this->toolbar->addText($renderer->render($view_control));
        }
    }

    /**
     *
     */
    protected function switchToTiles()
    {
        UserSettingsRepository::changeViewType(
            $this->user->getId(),
            filter_input(INPUT_GET, 'ref_id'),
            UserSettingsRepository::VIEW_TYPE_TILES
        );
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function switchToList()
    {
        UserSettingsRepository::changeViewType(
            $this->user->getId(),
            filter_input(INPUT_GET, 'ref_id'),
            UserSettingsRepository::VIEW_TYPE_LIST
        );
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *    called by 'tiles per page' selector
     */
    protected function changeTileLimit()
    {
        $tile_limit = filter_input(INPUT_POST, 'tiles_per_page');
        if (in_array($tile_limit, [4, 8, 12, 16])) {
            UserSettingsRepository::changeTileLimit(
                $this->user->getId(),
                filter_input(INPUT_GET, 'ref_id'),
                $tile_limit
            );
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function loadAjaxCodeForList()
    {
        $ajax_link = $this->dic->http()->request()->getRequestTarget();
        $ajax_link .= '&async=true';

        $ajax = "$.ajax({
				    url: '{$ajax_link}',
				    dataType: 'html',
				    success: function(data){
				        xoctWaiter.hide();
				        $('div#xoct_table_placeholder').replaceWith($(data));
				    }
				});";
        $this->main_tpl->addOnLoadCode('xoctWaiter.show();');
        $this->main_tpl->addOnLoadCode($ajax);
    }

    /**
     *
     */
    protected function loadAjaxCodeForTiles()
    {
        $ajax_link = $this->dic->http()->request()->getRequestTarget();
        $ajax_link .= '&async=true';

        $ajax = "$.ajax({
				    url: '{$ajax_link}',
				    dataType: 'html',
				    success: function(data){
				        xoctWaiter.hide();
				        $('div#xoct_tiles_placeholder').replaceWith($(data));
				    }
				});";
        $this->main_tpl->addOnLoadCode('xoctWaiter.show();');
        $this->main_tpl->addOnLoadCode($ajax);
    }

    /**
     * ajax call
     * @return never
     */
    public function asyncGetTableGUI(): void
    {
        echo $this->getTableGUI();
        exit();
    }

    public function getTableGUI(): string
    {
        $modals_html = $this->getModalsHTML();
        $xoctEventTableGUI = $this->eventTableBuilder->table($this, self::CMD_STANDARD, $this->objectSettings);
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
        return $html . $modals_html;
    }

    /**
     * ajax call
     * @return never
     */
    public function asyncGetTilesGUI(): void
    {
        echo $this->getTilesGUI();
        exit();
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function getTilesGUI(): string
    {
        $xoctEventTileGUI = $this->eventTableBuilder->tiles($this, $this->objectSettings);
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
        return $html;
    }

    /**
     *
     */
    protected function add()
    {
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->upload(
            $this->ctrl->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        xoctWaiterGUI::initJS();
        $this->main_tpl->addOnLoadCode(
            'window.onbeforeunload = function(){
                        xoctWaiter.show();
                    };'
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    /**
     *
     */
    protected function create()
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $form = $this->formBuilder->upload(
            $this->ctrl->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest($this->http->request());
        $data = $form->getData();

        if (!$data) {
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

        if ($data[EventFormBuilder::F_ACCEPT_EULA][EventFormBuilder::F_ACCEPT_EULA]) {
            ToUManager::setToUAccepted($this->user->getId());
        }

        $metadata = $data['metadata']['object'];
        $metadata->addField(
            (new MetadataField(MDFieldDefinition::F_IS_PART_OF, MDDataType::text()))
                ->withValue($this->objectSettings->getSeriesIdentifier())
        );

        $this->event_repository->upload(
            new UploadEventRequest(
                new UploadEventRequestPayload(
                    $metadata,
                    $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance($this->user)),
                    new Processing(
                        PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
                        $this->getDefaultWorkflowParameters($data['workflow_configuration']['object'] ?? null)
                    ),
                    xoctUploadFile::getInstanceFromFileArray($data['file']['file'])
                )
            )
        );
        $this->uploadHandler->getUploadStorageService()->delete($data['file']['file']['id']);
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    public function getDefaultWorkflowParameters(?\stdClass $fromData = null): \stdClass
    {
        $WorkflowParameter = new WorkflowParameter();
        $defaultParameter = $fromData ?? new stdClass();
        $admin = ilObjOpenCastAccess::hasPermission('edit_videos');
        foreach ($WorkflowParameter::get() as $param) {
            $id = $param->getId();
            $defaultValue = $admin ? $param->getDefaultValueAdmin() : $param->getDefaultValueMember();

            if (!isset($fromData->$id) && $defaultValue == WorkflowParameter::VALUE_ALWAYS_ACTIVE) {
                $defaultParameter->$id = "true";
            }
        }
        return $defaultParameter;
    }

    /**
     *
     */
    protected function schedule()
    {
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            $this->ctrl->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    /**
     *
     */
    protected function createScheduled()
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            $this->ctrl->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest($this->http->request());
        $data = $form->getData();

        if (!$data) {
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

        if ($data[EventFormBuilder::F_ACCEPT_EULA][EventFormBuilder::F_ACCEPT_EULA]) {
            ToUManager::setToUAccepted($this->user->getId());
        }

        $metadata = $data['metadata']['object'];
        $metadata->addField(
            (new MetadataField(MDFieldDefinition::F_IS_PART_OF, MDDataType::text()))
                ->withValue($this->objectSettings->getSeriesIdentifier())
        );

        try {
            $this->event_repository->schedule(
                new ScheduleEventRequest(
                    new ScheduleEventRequestPayload(
                        $metadata,
                        $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance($this->dic->user())),
                        $data['scheduling']['object'],
                        new Processing(
                            PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
                            $data['workflow_configuration']['object']
                        )
                    )
                )
            );
        } catch (xoctException $e) {
            $this->checkAndShowConflictMessage($e);
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

        ilUtil::sendSuccess($this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    /**
     * @throws xoctException
     */
    private function checkAndShowConflictMessage(xoctException $e): bool
    {
        if ($e->getCode() == xoctException::API_CALL_STATUS_409) {
            $conflicts = json_decode(substr($e->getMessage(), 10), true);
            $message = $this->txt('msg_scheduling_conflict') . '<br>';
            foreach ($conflicts as $conflict) {
                $message .= '<br>' . $conflict['title'] . '<br>' . date(
                    'Y.m.d H:i:s',
                    strtotime($conflict['start'])
                ) . ' - '
                    . date('Y.m.d H:i:s', strtotime($conflict['end'])) . '<br>';
            }
            ilUtil::sendFailure($message);

            return false;
        }
        throw $e;
    }

    /**
     * @throws DICException
     * @throws ilDateTimeException
     * @throws xoctException
     */
    protected function edit()
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE),
            $event->getMetadata(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    protected function editScheduled(): void
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update_scheduled(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE_SCHEDULED),
            $event->getMetadata(),
            $event->getScheduling(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    /**
     *
     */
    public function opencaststudio(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
        $this->addCurrentUserToProducers();
        // redirect to oc studio
        $base = rtrim(PluginConfig::getConfig(PluginConfig::F_API_BASE), "/");
        $base = str_replace('/api', '', $base);

        $studio_link = $base . '/studio';

        // get the custom url for the studio.
        $custom_url = PluginConfig::getConfig(PluginConfig::F_STUDIO_URL);
        if (!empty($custom_url)) {
            $studio_link = rtrim($custom_url, "/");
        }

        $return_link = ILIAS_HTTP_PATH . '/'
            . $this->ctrl->getLinkTarget($this, self::CMD_STANDARD);

        $studio_link .= '?upload.seriesId=' . $this->objectSettings->getSeriesIdentifier()
            . '&return.label=ILIAS'
            . '&return.target=' . urlencode($return_link);
        $this->ctrl->redirectToURL($studio_link);
    }

    /**
     *
     */
    public function cut(): void
    {
        $xoctUser = xoctUser::getInstance($this->user);
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $this->addCurrentUserToProducers();

        // redirect
        $cutting_link = $event->publications()->getCuttingLink();
        $this->ctrl->redirectToURL($cutting_link);
    }

    /**
     * @throws xoctException
     */
    public function download(): void
    {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_STRING);
        $publication_id = filter_input(INPUT_GET, 'pub_id', FILTER_SANITIZE_STRING);
        $event = $this->event_repository->find($event_id);
        $download_publications = $event->publications()->getDownloadPublications();
        if ($publication_id) {
            $publication = array_filter($download_publications, function ($publication) use ($publication_id): bool {
                return $publication->getId() === $publication_id;
            });
            $publication = array_shift($publication);
        } else {
            $publication = array_shift($download_publications);
        }
        $url = $publication->getUrl();
        $extension = pathinfo($url)['extension'];
        $url = PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;

        if (PluginConfig::getConfig(PluginConfig::F_EXT_DL_SOURCE)) {
            // Open external source page
            header('Location: ' . $url);
        } else {
            // get filesize
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($ch);

            // deliver file
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $publication->getMediatype());
            header('Content-Disposition: attachment; filename="' . $event->getTitle() . '.' . $extension . '"');
            header('Content-Length: ' . $size);
            readfile($url);
        }

        exit;
    }

    /**
     * @throws xoctException
     */
    public function annotate(): void
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);

        // check access
        if (ilObjOpenCastAccess::hasPermission('edit_videos') || ilObjOpenCastAccess::hasWriteAccess()) {
            $this->addCurrentUserToProducers();
        }

        // redirect
        $annotation_link = $event->publications()->getAnnotationLink(
            filter_input(INPUT_GET, 'ref_id', FILTER_SANITIZE_NUMBER_INT)
        );
        $this->ctrl->redirectToURL($annotation_link);
    }

    /**
     *
     */
    public function setOnline(): void
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $event->getXoctEventAdditions()->setIsOnline(true);
        $event->getXoctEventAdditions()->update();
        $this->cancel();
    }

    /**
     *
     */
    public function setOffline(): void
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $event->getXoctEventAdditions()->setIsOnline(false);
        $event->getXoctEventAdditions()->update();
        $this->cancel();
    }

    /**
     *
     * @throws xoctException|DICException
     */
    protected function update()
    {
        $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER, FILTER_SANITIZE_STRING));
        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE),
            $event->getMetadata(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest($this->http->request());
        $data = $form->getData();

        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if (!$data) {
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }
        $data = $data[0];

        $this->event_repository->update(
            new UpdateEventRequest(
                $event->getIdentifier(),
                new UpdateEventRequestPayload(
                    $data['object']
                )
            )
        );
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     * @throws xoctException|DICException
     */
    protected function updateScheduled()
    {
        $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER, FILTER_SANITIZE_STRING));
        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        // TODO: metadata/scheduling should not be necessary here
        $form = $this->formBuilder->update_scheduled(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE_SCHEDULED),
            $event->getMetadata(),
            $event->getScheduling(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest($this->http->request());
        $data = $form->getData();

        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if (!$data) {
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

        $scheduling = $data['scheduling']['object'] ?? null;
        $this->event_repository->update(
            new UpdateEventRequest(
                $event->getIdentifier(),
                new UpdateEventRequestPayload(
                    $data['metadata']['object'],
                    null,
                    $scheduling
                )
            )
        );
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     * @throws DICException
     * @throws xoctException
     */
    protected function republish()
    {
        $post_body = $this->http->request()->getParsedBody();
        if (isset($post_body['workflow_id']) && is_string($post_body['workflow_id'])
            && isset($post_body['republish_event_id']) && is_string($post_body['republish_event_id'])
        ) {
            $workflow_id = strip_tags($post_body['workflow_id']);
            $event_id = strip_tags($post_body['republish_event_id']);
            $workflow = $this->workflowRepository->getById($workflow_id);
            if (!ilObjOpenCastAccess::checkAction(
                ilObjOpenCastAccess::ACTION_EDIT_EVENT,
                $this->event_repository->find($event_id)
            )
                || is_null($workflow)) {
                ilUtil::sendFailure($this->txt('msg_no_access'), true);
                $this->cancel();
            }
            $configurations = [];
            foreach (array_filter(explode(',', $workflow->getParameters())) as $param) {
                $configurations[$param] = 'true';
            }

            $workflow_instance = $this->api->routes()->workflowsApi->run(
                $event_id,
                $workflow->getWorkflowId(),
                $configurations,
                true,
                true
            );
            ilUtil::sendSuccess($this->txt('msg_republish_started'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
    }

    /**
     *
     */
    protected function removeInvitations()
    {
        foreach (PermissionGrant::get() as $xoctInvitation) {
            $xoctInvitation->delete();
        }
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        if (count($event->publications()->getPublications()) && PluginConfig::getConfig(
            PluginConfig::F_WORKFLOW_UNPUBLISH
        )) {
            $header_text = $this->txt('unpublish_confirm');
            $action_text = 'unpublish';
        } else {
            $header_text = $this->objectSettings->getDuplicatesOnSystem() ? $this->txt(
                'delete_confirm_w_duplicates'
            ) : $this->txt('delete_confirm');
            $action_text = 'delete';
        }
        $ilConfirmationGUI->setHeaderText($header_text);
        $ilConfirmationGUI->setCancel($this->txt('cancel'), self::CMD_CANCEL);
        $ilConfirmationGUI->setConfirm($this->txt($action_text), self::CMD_DELETE);
        $ilConfirmationGUI->addItem(self::IDENTIFIER, $event->getIdentifier(), $event->getTitle());
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    /**
     * @throws DICException
     * @throws xoctException
     */
    protected function delete()
    {
        $event = $this->event_repository->find($_POST[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
        if (count($event->publications()->getPublications()) && PluginConfig::getConfig(
            PluginConfig::F_WORKFLOW_UNPUBLISH
        )) {
            try {
                $this->unpublish($event);
                ilUtil::sendSuccess($this->txt('msg_unpublish_started'), true);
            } catch (xoctException $e) {
                if ($e->getCode() == 409) {
                    ilUtil::sendInfo($this->txt('msg_currently_unpublishing'), true);
                } else {
                    throw $e;
                }
            }
        } else {
            $this->event_repository->delete($event->getIdentifier());
            ilUtil::sendSuccess($this->txt('msg_deleted'), true);
        }
        $this->cancel();
    }

    /**
     * @throws xoctException
     */
    private function unpublish(Event $event): bool
    {
        $workflow = PluginConfig::getConfig(PluginConfig::F_WORKFLOW_UNPUBLISH);
        $workflow_instance = $this->api->routes()->workflowsApi->run($event->getIdentifier(), $workflow);
        return true;
    }

    /**
     *
     */
    protected function clearCache()
    {
        CacheFactory::getInstance()->flush();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     * @return string
     */
    protected function getModalsHTML()
    {
        $modals_html = '';
        foreach ($this->getModals()->getAllComponents() as $modal) {
            $modals_html .= $this->ui->renderer()->renderAsync($modal);
        }

        return $modals_html;
    }

    /**
     *
     */
    protected function reportDate()
    {
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
            $message = $this->getDateReportMessage($_POST['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «geplante Termine anpassen»';
            $report = new Report();
            $report->setType(Report::TYPE_DATE)
                   ->setUserId($this->user->getId())
                   ->setSubject($subject)
                   ->setMessage($message)
                   ->create();
        }
        ilUtil::sendSuccess($this->plugin->txt('msg_date_report_sent'), true);
        $this->ctrl->redirect($this);
    }

    /**
     *
     */
    protected function reportQuality()
    {
        $event = $this->event_repository->find($_POST['event_id']);
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $event)) {
            $message = $this->getQualityReportMessage($event, $_POST['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «Qualitätsprobleme»';

            $report = new Report();
            $report->setType(Report::TYPE_QUALITY)
                   ->setUserId($this->user->getId())
                   ->setSubject($subject)
                   ->setMessage($message)
                   ->create();
        }
        ilUtil::sendSuccess($this->plugin->txt('msg_quality_report_sent'), true);
        $this->ctrl->redirect($this);
    }

    /**
     * @param       $message
     */
    protected function getQualityReportMessage(Event $event, $message): string
    {
        $link = ilLink::_getStaticLink(
            $_GET['ref_id'],
            ilOpenCastPlugin::PLUGIN_ID,
            true
        );
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($_GET['ref_id']);
        $crs_grp_role = ilObjOpenCast::_getCourseOrGroupRole();
        return "Dies ist eine automatische Benachrichtigung des ILIAS Opencast Plugins <br><br>"
            . "Es gab eine neue Meldung im Bereich «Qualitätsprobleme melden». <br><br>"
            . "<b>Benutzer/in:</b> " . $this->user->getLogin() . ", " . $this->user->getEmail() . " <br>"
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
    }

    /**
     * @param $message
     */
    protected function getDateReportMessage($message): string
    {
        $link = ilLink::_getStaticLink($_GET['ref_id'], ilOpenCastPlugin::PLUGIN_ID);
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($_GET['ref_id']);
        return "Dies ist eine automatische Benachrichtigung des ILIAS Opencast Plugins <br><br>"
            . "Es gab eine neue Meldung im Bereich «geplante Termine anpassen». <br><br>"
            . "<b>Benutzer/in:</b> " . $this->user->getLogin() . ", " . $this->user->getEmail() . " <br><br>"
            . "<b>Opencast Serie in ILIAS:</b> $link<br>"
            . "<b>Titel Opencast Serie:</b> {$series->getILIASObject()->getTitle()}<br>"
            . "<b>ID Opencast Serie:</b> {$series->getSeriesIdentifier()}<br><br>"
            . "<b>Nachrichtentext:</b> <br>"
            . "<hr>"
            . nl2br($message) . "<br>"
            . "<hr>";
    }

    /**
     * @param $key
     *
     * @throws DICException
     */
    public function txt($key): string
    {
        return $this->plugin->txt('event_' . $key);
    }

    public function getObjId(): int
    {
        return $this->objectSettings->getObjId();
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    public function getModals(): EventModals
    {
        global $DIC;
        if (is_null($this->modals)) {
            $modals = new EventModals(
                $this,
                ilOpenCastPlugin::getInstance(),
                $DIC,
                $this->workflowRepository
            );
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
     */
    protected function getIntroTextHTML()
    {
        $intro_text = '';
        if ($this->objectSettings->getIntroductionText() !== '' && $this->objectSettings->getIntroductionText(
            ) !== '0') {
            $intro = new ilTemplate(
                './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/tpl.intro.html',
                '',
                true,
                true
            );
            $intro->setVariable('INTRO', nl2br($this->objectSettings->getIntroductionText()));
            $intro_text = $intro->get();
        }
        return $intro_text;
    }

    protected function createHyperlinks(string $intro_text): string
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w]+\)|([^[:punct:]\s]|/))#', $intro_text, $urls);
        preg_match_all('#\bwww[.][^,\s()<>]+(?:\([\w]+\)|([^[:punct:]\s]|/))#', $intro_text, $urls_www);
        foreach ($urls[0] as $url) {
            $replacement = "<a href='" . $url . "'>" . $url . "</a>";
            $intro_text = str_replace($url, $replacement, $intro_text);
        }
        foreach ($urls_www[0] as $url) {
            $replacement = "<a href='https://" . $url . "'>" . $url . "</a>";
            $intro_text = str_replace($url, $replacement, $intro_text);
        }
        return $intro_text;
    }

    protected function addCurrentUserToProducers(): void
    {
        $xoctUser = xoctUser::getInstance($this->user);
        // add user to ilias producers
        $sleep = false;
        try {
            if ($group_producers = PluginConfig::getConfig(PluginConfig::F_GROUP_PRODUCERS)) {
                $ilias_producers = Group::find($group_producers);
                $sleep = $ilias_producers->addMember($xoctUser);
            }
        } catch (xoctException $e) {
        }

        // add user to series producers
        if ($this->objectSettings->getSeriesIdentifier() !== null) {
            $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());
            if ($series->getAccessPolicies()->merge($this->ACLUtils->getUserRolesACL($xoctUser))) {
                $this->seriesRepository->updateACL(
                    new UpdateSeriesACLRequest(
                        $series->getIdentifier(),
                        new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
                    )
                );
                $sleep = true;
            }
        }

        // race condition fix (opencast takes some time to actually update the ACL)
        if ($sleep) {
            sleep(3);
        }
    }
}
