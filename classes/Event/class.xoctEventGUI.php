<?php

declare(strict_types=1);

use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
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
use srag\Plugins\Opencast\Model\Cache\Services;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\OutputResponse;

/**
 * Class xoctEventGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_Calls      xoctEventGUI: xoctPlayerGUI
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI
{
    use OutputResponse;
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
    // public const CMD_REPUBLISH = 'republish';
    public const CMD_START_WORKFLOW = 'startWorkflow';
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
     * @var WaitOverlay
     */
    private $wait_overlay;
    /**
     * @var Services
     */
    private $cache;
    /**
     * @var int
     */
    private $ref_id;
    /**
     * @var \ILIAS\UI\Implementation\DefaultRenderer
     */
    protected $custom_renderer;

    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var EventModals|null
     */
    protected $modals = null;
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
     * @var xoctFileUploadHandlerGUI
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
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;

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
        global $DIC, $opencastContainer;
        parent::__construct();

        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->ui = $DIC->ui();
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
        $this->wait_overlay = new WaitOverlay($this->main_tpl);
        $this->cache = $opencastContainer->get(Services::class);
        $this->ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
    }

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
                    $this->main_tpl->setOnScreenMessage('failure', $this->txt("msg_no_access"), true);
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
            case strtolower(xoctFileUploadHandlerGUI::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    $this->main_tpl->setOnScreenMessage('failure', $this->txt("msg_no_access"), true);
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

    protected function performCommand(string $cmd): void
    {
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_EVENTS);

        // Adding the top level index.js.
        $this->main_tpl->addJavaScript($this->plugin->getDirectory() . '/js/opencast/dist/index.js');

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

        // Start Workflow stylesheet
        $this->main_tpl->addCss(
            $this->plugin->getDirectory() . '/templates/default/startworkflow_modal.css'
        );

        switch ($cmd) {
            case self::CMD_STANDARD:
                $this->prepareContent();
                break;
            default:
        }
        parent::performCommand($cmd);
    }


    protected function prepareContent(): void
    {
        $this->wait_overlay->onLinkClick('#rep_robj_xoct_event_clear_cache');
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
            $b = ilLinkButton::getInstance();
            $b->setId('xoct_report_date_button');
            $b->setCaption('rep_robj_xoct_event_report_date_modification');
            $b->addCSSClass('hidden');

            $this->toolbar->addButtonInstance($b);
        }
    }

    /**
     * asynchronous loading of tableGUI
     */
    protected function index(): void
    {
        $filter_html = null;
        ilChangeEvent::_recordReadEvent(
            $this->parent_gui->getObject()->getType(),
            $this->parent_gui->getObject()->getRefId(),
            $this->objectSettings->getObjId(),
            $this->user->getId()
        );

        switch (UserSettingsRepository::getViewTypeForUser($this->user->getId(), $this->ref_id)) {
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
                        $this->ref_id
                    ) .
                    ' for user with id ' . $this->user->getId()
                );
        }

        $filter_html = $this->dic->ui()->renderer()->render(
            $this->eventTableBuilder->filter(
                $this->dic->ctrl()->getFormAction($this, self::CMD_STANDARD, '')
            )
        );
        $intro_text = $this->createHyperlinks($this->getIntroTextHTML());
        $this->main_tpl->setContent($intro_text . $filter_html . $html);
    }

    protected function indexList(): string
    {
        $this->initViewSwitcherHTML('list');

        $key = xoctEventTableGUI::getGeneratedPrefix($this->getObjId()) . '_xpt';


        if (isset($this->http->request()->getQueryParams()[$key])
            || $this->http->request()->getParsedBody() !== []
            || PluginConfig::getConfig(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY)) {
            // load table synchronously
            return $this->getTableGUI();
        }

        if (isset($this->http->request()->getQueryParams()['async'])) {
            $this->asyncGetTableGUI();
        }

        $this->main_tpl->addJavascript("./Services/Table/js/ServiceTable.js");
        $this->loadAjaxCodeForList();    // load table asynchronously
        return '<div id="xoct_table_placeholder"></div>';
    }

    protected function indexTiles(): string
    {
        $this->initViewSwitcherHTML('tiles');

        if (PluginConfig::getConfig(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY)) {
            return $this->getTilesGUI();
        }

        if (isset($this->http->request()->getQueryParams()['async'])) {
            $this->asyncGetTilesGUI();
        }

        $this->loadAjaxCodeForTiles();    // load tiles asynchronously
        return '<div id="xoct_tiles_placeholder"></div>';
    }

    protected function initViewSwitcherHTML(string $active): void
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

    protected function switchToTiles(): void
    {
        UserSettingsRepository::changeViewType(
            $this->user->getId(),
            $this->ref_id,
            UserSettingsRepository::VIEW_TYPE_TILES
        );
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function switchToList(): void
    {
        UserSettingsRepository::changeViewType(
            $this->user->getId(),
            $this->ref_id,
            UserSettingsRepository::VIEW_TYPE_LIST
        );
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function changeTileLimit(): void
    {
        $tile_limit = (int) ($this->http->request()->getParsedBody()['tiles_per_page'] ?? 0);

        if (in_array($tile_limit, [4, 8, 12, 16])) {
            UserSettingsRepository::changeTileLimit(
                $this->user->getId(),
                $this->ref_id,
                $tile_limit
            );
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function loadAjaxCodeForList(): void
    {
        $ajax_link = $this->dic->http()->request()->getRequestTarget();
        $ajax_link .= '&async=true';

        $ajax = "$.ajax({
				    url: '$ajax_link',
				    dataType: 'html',
				    success: function(data){
				        il.Opencast.UI.waitOverlay.hide();
				        $('div#xoct_table_placeholder').replaceWith($(data));
				    }
				});";
        $this->main_tpl->addOnLoadCode('il.Opencast.UI.waitOverlay.show();');
        $this->main_tpl->addOnLoadCode($ajax);
    }

    protected function loadAjaxCodeForTiles(): void
    {
        $ajax_link = $this->dic->http()->request()->getRequestTarget();
        $ajax_link .= '&async=true';

        $ajax = "$.ajax({
				    url: '$ajax_link',
				    dataType: 'html',
				    success: function(data){
				        il.Opencast.UI.waitOverlay.hide();
				        $('div#xoct_tiles_placeholder').replaceWith($(data));
				        il.Opencast.UI.Tiles.init();
				    }
				});";
        $this->main_tpl->addOnLoadCode('il.Opencast.UI.waitOverlay.show();');
        $this->main_tpl->addOnLoadCode($ajax);
    }

    /**
     * @return never
     */
    public function asyncGetTableGUI(): void
    {
        $this->sendReponse($this->getTableGUI());
    }

    public function getTableGUI(): string
    {
        $xoctEventTableGUI = $this->eventTableBuilder->table($this, self::CMD_STANDARD, $this->objectSettings);

        return $this->prependModalsAndTrigger($xoctEventTableGUI);
    }

    /**
     * ajax call
     * @return never
     */
    public function asyncGetTilesGUI(): void
    {
        $this->sendReponse($this->getTilesGUI());
    }

    protected function getTilesGUI(): string
    {
        $xoctEventTileGUI = $this->eventTableBuilder->tiles($this, $this->objectSettings);

        return $this->prependModalsAndTrigger($xoctEventTileGUI);
    }

    private function prependModalsAndTrigger(object $providing_gui): string
    {
        $modals_html = $this->getModalsHTML();
        switch (true) {
            case $providing_gui instanceof xoctEventTableGUI:
            case $providing_gui instanceof xoctEventTileGUI:
                $html = $providing_gui->getHTML();
                $has_scheduled_events = $providing_gui->hasScheduledEvents();
                break;
            default:
                throw new xoctException(
                    xoctException::INTERNAL_ERROR,
                    'Invalid type ' . get_class($providing_gui) . ' for providing gui'
                );
        }

        if ($has_scheduled_events) {
            $signal = $this->getModals()->getReportDateModal()->getShowSignal()->getId();
            $modals_html .= "<script type='text/javascript'>
                        window.onload = function(event) {
                            $('#xoct_report_date_button').removeClass('hidden');
                            $('#xoct_report_date_button').on('click', function(){
                                $(this).trigger('$signal',
                                {
                                    'id' : '$signal', 'event' : 'click',
                                    'triggerer' : $(this),
                                    'options' : JSON.parse('[]')
                                });
                            });    
                        };
                    </script>";
        }

        return $html . $modals_html;
    }

    /**
     *
     */
    protected function add(): void
    {
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->upload(
            $this->ctrl->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->wait_overlay->onUnload();

        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    protected function create(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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

        if ($data[EventFormBuilder::F_ACCEPT_EULA][EventFormBuilder::F_ACCEPT_EULA] ?? false) {
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
        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
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

            if (!isset($fromData->{$id}) && $defaultValue == WorkflowParameter::VALUE_ALWAYS_ACTIVE) {
                $defaultParameter->{$id} = "true";
            }
        }
        return $defaultParameter;
    }

    protected function schedule(): void
    {
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            $this->ctrl->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    protected function createScheduled(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if ($this->objectSettings->getDuplicatesOnSystem()) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
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
                            $this->getDefaultWorkflowParameters($data['workflow_configuration']['object'] ?? null)
                        )
                    )
                )
            );
        } catch (xoctException $e) {
            $this->checkAndShowConflictMessage($e);
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    private function checkAndShowConflictMessage(xoctException $e): void
    {
        if ($e->getCode() === xoctException::API_CALL_STATUS_409) {
            $conflicts = (array) json_decode(substr($e->getMessage(), 10), true);
            $message = $this->txt('msg_scheduling_conflict') . '<br>';
            foreach ($conflicts as $conflict) {
                $message .= '<br>' . $conflict['title'] . '<br>' . date(
                        'Y.m.d H:i:s',
                        strtotime($conflict['start'])
                    ) . ' - '
                    . date('Y.m.d H:i:s', strtotime($conflict['end'])) . '<br>';
            }
            $this->main_tpl->setOnScreenMessage('failure', $message);
            return;
        }
        throw $e;
    }

    protected function edit(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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

    public function opencaststudio(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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


    public function cut(): void
    {
        $xoctUser = xoctUser::getInstance($this->user);
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $event, $xoctUser)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $this->addCurrentUserToProducers();

        // redirect
        $cutting_link = $event->publications()->getCuttingLink();
        $this->ctrl->redirectToURL($cutting_link);
    }


    public function download(): void
    {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_STRING);
        $publication_id = filter_input(INPUT_GET, 'pub_id', FILTER_SANITIZE_STRING);
        $usage_type = filter_input(INPUT_GET, 'usage_type', FILTER_SANITIZE_STRING);
        $usage_id = filter_input(INPUT_GET, 'usage_id', FILTER_SANITIZE_STRING);
        $event = $this->event_repository->find($event_id);
        $download_publications = $event->publications()->getDownloadPublications();
        // Now that we have multiple sub-usages, we first check for publication_id which is passed by the multi-dropdowns.
        if ($publication_id) {
            $publication = array_filter($download_publications, function ($publication) use ($publication_id): bool {
                return $publication->getId() === $publication_id;
            });
            $publication = reset($publication);
        } else {
            // If this is not multi-download dropdown, then it has to have the usage_type and usage_id parameters identified.
            if (!empty($usage_type) && !empty($usage_id)) {
                $publication = array_filter(
                    $download_publications,
                    function ($publication) use ($usage_type, $usage_id): bool {
                        return $publication->usage_id == $usage_id && $publication->usage_type === $usage_type;
                    }
                );
                $publication = reset($publication);
            } else {
                // As a fallback we take out the last publication, if non of the above has been met!
                $publication = reset($download_publications);
            }
        }

        if (empty($publication)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_download_publication'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }

        $url = $publication->getUrl();
        $extension = pathinfo($url)['extension'];
        $url = PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;

        // if (PluginConfig::getConfig(PluginConfig::F_EXT_DL_SOURCE)) {
        if (property_exists($publication, 'ext_dl_source') && $publication->ext_dl_source) {
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

        $this->closeResponse();
    }


    public function annotate(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        // check access
        if (ilObjOpenCastAccess::hasPermission('edit_videos') || ilObjOpenCastAccess::hasWriteAccess()) {
            $this->addCurrentUserToProducers();
        }

        // redirect
        $annotation_link = $event->publications()->getAnnotationLink(
            $this->ref_id
        );
        $this->ctrl->redirectToURL($annotation_link);
    }

    public function setOnline(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);
        $event->getXoctEventAdditions()->setIsOnline(true);
        $event->getXoctEventAdditions()->update();
        $this->cancel();
    }


    public function setOffline(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);
        $event->getXoctEventAdditions()->setIsOnline(false);
        $event->getXoctEventAdditions()->update();
        $this->cancel();
    }


    protected function update(): void
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
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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
        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }


    protected function updateScheduled(): void
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
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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
        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }


    protected function startWorkflow(): void
    {
        $post_body = $this->http->request()->getParsedBody();
        if (isset($post_body['workflow_id']) && is_string($post_body['workflow_id'])
            && isset($post_body['startworkflow_event_id']) && is_string($post_body['startworkflow_event_id'])
        ) {
            $workflow_id = (int) strip_tags($post_body['workflow_id']);
            $event_id = (string) strip_tags($post_body['startworkflow_event_id']);
            $workflow = $this->workflowRepository->getById($workflow_id);
            if (!ilObjOpenCastAccess::checkAction(
                ilObjOpenCastAccess::ACTION_EDIT_EVENT,
                $this->event_repository->find($event_id)
            )
                || is_null($workflow)) {
                $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
                $this->cancel();
            }

            $received_configs = [];
            if (!empty($post_body[$workflow_id])) {
                $received_configs = $post_body[$workflow_id];
            }
            $default_configs = $this->workflowRepository->getConfigPanelAsArrayById($workflow_id);
            $configurations = [];

            foreach ($default_configs as $key => $config_data) {
                $value = $config_data['value'];
                $type = $config_data['type'];
                if (in_array($key, array_keys($received_configs), true)) {
                    $received_value = $received_configs[$key];
                    // Take care of datetime conversion.
                    if (strpos($type, 'datetime') !== false) {
                        $datetime = new DateTimeImmutable($received_value);
                        $received_value = $datetime->format('Y-m-d\TH:i:s\Z');
                        $value = $received_value;
                    } elseif ($type == 'text') {
                        $value = strip_tags($received_value);
                    } elseif ($type == 'number') {
                        $value = intval($received_value);
                    } else {
                        $value = $received_value;
                    }
                }
                // Take care of boolean conversion.
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $configurations[$key] = (string) $value;
            }

            $workflow_instance = $this->api->routes()->workflowsApi->run(
                $event_id,
                $workflow->getWorkflowId(),
                $configurations,
                true,
                true
            );
            $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_republish_started'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }
    }


    protected function removeInvitations(): void
    {
        foreach (PermissionGrant::get() as $xoctInvitation) {
            $xoctInvitation->delete();
        }
        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }


    protected function confirmDelete(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
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

    protected function delete(): void
    {
        $event = $this->event_repository->find($this->http->request()->getParsedBody()[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }
        if (count($event->publications()->getPublications()) && PluginConfig::getConfig(
            PluginConfig::F_WORKFLOW_UNPUBLISH
        )) {
            try {
                $this->unpublish($event);
                $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_unpublish_started'), true);
            } catch (xoctException $e) {
                if ($e->getCode() == 409) {
                    $this->main_tpl->setOnScreenMessage('info', $this->txt('msg_currently_unpublishing'), true);
                } else {
                    throw $e;
                }
            }
        } else {
            $this->event_repository->delete($event->getIdentifier());
            $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_deleted'), true);
        }
        $this->cancel();
    }


    private function unpublish(Event $event): void
    {
        $workflow = PluginConfig::getConfig(PluginConfig::F_WORKFLOW_UNPUBLISH);
        $this->api->routes()->workflowsApi->run($event->getIdentifier(), $workflow);
    }

    protected function clearCache(): void
    {
        $this->cache->flushAdapter();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function getModalsHTML(): string
    {
        $modals_html = '';
        foreach ($this->getModals()->getAllComponents() as $modal) {
            $modals_html .= $this->ui->renderer()->renderAsync($modal);
        }

        return $modals_html;
    }

    protected function reportDate(): void
    {
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)) {
            $message = $this->getDateReportMessage($this->http->request()->getParsedBody()['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «geplante Termine anpassen»';
            $report = new Report();
            $report->setType(Report::TYPE_DATE)
                   ->setUserId($this->user->getId())
                   ->setSubject($subject)
                   ->setMessage($message)
                   ->create();
        }
        $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_date_report_sent'), true);
        $this->ctrl->redirect($this);
    }

    protected function reportQuality(): void
    {
        $event = $this->event_repository->find($this->http->request()->getParsedBody()['event_id']);
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $event)) {
            $message = $this->getQualityReportMessage($event, $this->http->request()->getParsedBody()['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «Qualitätsprobleme»';

            $report = new Report();
            $report->setType(Report::TYPE_QUALITY)
                   ->setUserId($this->user->getId())
                   ->setSubject($subject)
                   ->setMessage($message)
                   ->create();
        }
        $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_quality_report_sent'), true);
        $this->ctrl->redirect($this);
    }


    protected function getQualityReportMessage(
        Event $event,
        string $message
    ): string {
        $link = ilLink::_getStaticLink(
            $this->ref_id,
            ilOpenCastPlugin::PLUGIN_ID,
            true
        );
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($this->ref_id);
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

    protected function getDateReportMessage(string $message): string
    {
        $link = ilLink::_getStaticLink($this->ref_id, ilOpenCastPlugin::PLUGIN_ID);
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $series = xoctInternalAPI::getInstance()->series()->read($this->ref_id);
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


    public function txt($key): string
    {
        return $this->plugin->txt('event_' . $key);
    }

    public function getObjId(): int
    {
        return $this->objectSettings->getObjId();
    }


    public function getModals(): EventModals
    {
        global $DIC;
        if ($this->modals === null) {
            $modals = new EventModals(
                $this,
                ilOpenCastPlugin::getInstance(),
                $DIC,
                $this->workflowRepository
            );
            $modals->initWorkflows();
            $modals->initReportDate();
            $modals->initReportQuality();
            $this->modals = $modals;
            xoctEventRenderer::initModals($modals);
        }
        return $this->modals;
    }

    protected function getIntroTextHTML(): string
    {
        $intro_text = '';
        if ($this->objectSettings->getIntroductionText() !== '' && $this->objectSettings->getIntroductionText(
            ) !== '0') {
            $intro = new ilTemplate(
                './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/tpl.intro.html',
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
