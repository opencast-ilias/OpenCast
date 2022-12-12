<?php

use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\Exception\DICException;
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
    )
    {
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
        global $DIC;
        $this->ui_renderer = new \ILIAS\UI\Implementation\DefaultRenderer(
            new Loader($DIC, ilOpenCastPlugin::getInstance())
        );
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
                $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER));
                // check access
                if (!ilObjOpenCastAccess::hasReadAccessOnEvent($event, xoctUser::getInstance(self::dic()->user()), $this->objectSettings)) {
                    ilUtil::sendFailure($this->txt("msg_no_access"), true);
                    $this->cancel();
                }
                $xoctPlayerGUI = new xoctPlayerGUI(
                    $this->event_repository,
                    $this->paellaConfigStorageService,
                    $this->paellaConfigServiceFactory,
                    $this->objectSettings
                );
                self::dic()->ctrl()->forwardCommand($xoctPlayerGUI);
                break;
            case strtolower(xoctFileUploadHandler::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    ilUtil::sendFailure($this->txt("msg_no_access"), true);
                    $this->cancel();
                }
                self::dic()->ctrl()->forwardCommand($this->uploadHandler);
                break;
            default:
                $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        self::dic()->tabs()->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
        self::dic()->ui()->mainTemplate()->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.css');
        self::dic()->ui()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/events.js');    // init waiter
        self::dic()->ui()->mainTemplate()->addOnLoadCode(
            "$(document).on('shown.bs.dropdown', (e) => {
					$(e.target).children('.dropdown-menu').each((i, el) => {
						il.Util.fixPosition(el);
					});
				});"
        );    // fix action menu position bug
        self::dic()->ui()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getDirectory() . '/templates/default/reporting_modal.css');

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
        self::dic()->ui()->mainTemplate()->addJavascript("./src/UI/templates/js/Modal/modal.js");
        self::dic()->ui()->mainTemplate()->addOnLoadCode('xoctEvent.init(\'' . json_encode([
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
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && PluginConfig::getConfig(PluginConfig::F_CREATE_SCHEDULED_ALLOWED)) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_schedule_new');
            $b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_SCHEDULE));
            $b->setPrimary(true);
            self::dic()->toolbar()->addButtonInstance($b);
        }

        // add "Opencast Studio" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT) && PluginConfig::getConfig(PluginConfig::F_STUDIO_ALLOWED)) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_opencast_studio');
            $b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_OPENCAST_STUDIO));
            $b->setPrimary(true);
            self::dic()->toolbar()->addButtonInstance($b);
        }

        // add "clear cache" button
        if (PluginConfig::getConfig(PluginConfig::F_ACTIVATE_CACHE)) {
            $b = ilLinkButton::getInstance();
            $b->setId('rep_robj_xoct_event_clear_cache');
            $b->setCaption('rep_robj_xoct_event_clear_cache');
            $b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_CLEAR_CACHE));
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
    protected function index()
    {
        ilChangeEvent::_recordReadEvent(
            $this->parent_gui->object->getType(),
            $this->parent_gui->object->getRefId(),
            $this->objectSettings->getObjId(),
            self::dic()->user()->getId()
        );

        switch (UserSettingsRepository::getViewTypeForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'))) {
            case UserSettingsRepository::VIEW_TYPE_LIST:
                $html = $this->indexList();
                break;
            case UserSettingsRepository::VIEW_TYPE_TILES:
                $html = $this->indexTiles();
                break;
            default:
                throw new xoctException(xoctException::INTERNAL_ERROR, 'Invalid view type ' .
                    UserSettingsRepository::getViewTypeForUser(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id')) .
                    ' for user with id ' . self::dic()->user()->getId());
        }

        if (xoct::isIlias7()) { // todo: remove when this is fixed https://mantis.ilias.de/view.php?id=32134
            $filter_html = $this->dic->ui()->renderer()->render(
                $this->eventTableBuilder->filter(
                    $this->dic->ctrl()->getFormAction($this, self::CMD_STANDARD, '', true)
                )
            );
        }
        $intro_text = $this->createHyperlinks($this->getIntroTextHTML());
        self::dic()->ui()->mainTemplate()->setContent($intro_text . $filter_html . $html);
    }

    /**
     * @return string
     * @throws DICException
     */
    protected function indexList()
    {
        $this->initViewSwitcherHTML('list');

        if (isset($_GET[xoctEventTableGUI::getGeneratedPrefix($this->getObjId()) . '_xpt'])
            || !empty($_POST)
            || PluginConfig::getConfig(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY)) {
            // load table synchronously
            return $this->getTableGUI();
        }

        if (isset($_GET['async'])) {
            return $this->asyncGetTableGUI();
        }

        self::dic()->ui()->mainTemplate()->addJavascript("./Services/Table/js/ServiceTable.js");
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
    protected function switchToTiles()
    {
        UserSettingsRepository::changeViewType(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), UserSettingsRepository::VIEW_TYPE_TILES);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function switchToList()
    {
        UserSettingsRepository::changeViewType(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), UserSettingsRepository::VIEW_TYPE_LIST);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *    called by 'tiles per page' selector
     */
    protected function changeTileLimit()
    {
        $tile_limit = filter_input(INPUT_POST, 'tiles_per_page');
        if (in_array($tile_limit, [4, 8, 12, 16])) {
            UserSettingsRepository::changeTileLimit(self::dic()->user()->getId(), filter_input(INPUT_GET, 'ref_id'), $tile_limit);
        }
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
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
        self::dic()->ui()->mainTemplate()->addOnLoadCode('xoctWaiter.show();');
        self::dic()->ui()->mainTemplate()->addOnLoadCode($ajax);
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
        self::dic()->ui()->mainTemplate()->addOnLoadCode('xoctWaiter.show();');
        self::dic()->ui()->mainTemplate()->addOnLoadCode($ajax);
    }

    /**
     * ajax call
     */
    public function asyncGetTableGUI()
    {
        echo $this->getTableGUI();
        exit();
    }

    public function getTableGUI()
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
     */
    public function asyncGetTilesGUI()
    {
        echo $this->getTilesGUI();
        exit();
    }

    /**
     * @return string
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
            ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->upload(
            self::dic()->ctrl()->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU(self::dic()->user()->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        xoctWaiterGUI::initJS();
        self::dic()->ui()->mainTemplate()->addOnLoadCode(
            'window.onbeforeunload = function(){
                        xoctWaiter.show();
                    };'
        );
        self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
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
            self::dic()->ctrl()->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU(self::dic()->user()->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest(self::dic()->http()->request());
        $data = $form->getData();


        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
            return;
        }

        if ($data[EventFormBuilder::F_ACCEPT_EULA][EventFormBuilder::F_ACCEPT_EULA]) {
            ToUManager::setToUAccepted(self::dic()->user()->getId());
        }

        $metadata = $data['metadata']['object'];
        $metadata->addField((new MetadataField(MDFieldDefinition::F_IS_PART_OF, MDDataType::text()))
            ->withValue($this->objectSettings->getSeriesIdentifier()));

        $this->event_repository->upload(new UploadEventRequest(new UploadEventRequestPayload(
            $metadata,
            $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance(self::dic()->user())),
            new Processing(
                PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
                $data['workflow_configuration']['object'] ?? $this->getDefaultWorkflowParameters()
            ),
            xoctUploadFile::getInstanceFromFileArray($data['file']['file'])
        )));
        $this->uploadHandler->getUploadStorageService()->delete($data['file']['file']['id']);
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    public function getDefaultWorkflowParameters()
    {
        $WorkflowParameter = new WorkflowParameter();
        $defaultParameter = new stdClass();
        $admin = ilObjOpenCastAccess::hasPermission('edit_videos');
        foreach ($WorkflowParameter::get() as $param) {
            $defaultValue = $admin ? $param->getDefaultValueAdmin() : $param->getDefaultValueMember();
            if ($defaultValue == WorkflowParameter::VALUE_ALWAYS_ACTIVE) {
                $id = $param->getId();
                $defaultParameter->$id = "true";
            }
        }
        return $defaultParameter ;
    }


    /**
     *
     */
    protected function schedule()
    {
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            self::dic()->ctrl()->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU(self::dic()->user()->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
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
            ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            self::dic()->ctrl()->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU(self::dic()->user()->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest(self::dic()->http()->request());
        $data = $form->getData();

        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
            return;
        }

        if ($data[EventFormBuilder::F_ACCEPT_EULA][EventFormBuilder::F_ACCEPT_EULA]) {
            ToUManager::setToUAccepted(self::dic()->user()->getId());
        }

        $metadata = $data['metadata']['object'];
        $metadata->addField((new MetadataField(MDFieldDefinition::F_IS_PART_OF, MDDataType::text()))
            ->withValue($this->objectSettings->getSeriesIdentifier()));

        try {
            $this->event_repository->schedule(new ScheduleEventRequest(new ScheduleEventRequestPayload(
                $metadata,
                $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance($this->dic->user())),
                $data['scheduling']['object'],
                new Processing(
                    PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
                    $data['workflow_configuration']['object']
                )
            )));
        } catch (xoctException $e) {
            $this->checkAndShowConflictMessage($e);
            self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
            return;
        }

        ilUtil::sendSuccess($this->txt('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
    }

    /**
     * @param xoctException $e
     * @return bool
     * @throws xoctException
     */
    private function checkAndShowConflictMessage(xoctException $e): bool
    {
        if ($e->getCode() == xoctException::API_CALL_STATUS_409) {
            $conflicts = json_decode(substr($e->getMessage(), 10), true);
            $message = $this->txt('msg_scheduling_conflict') . '<br>';
            foreach ($conflicts as $conflict) {
                $message .= '<br>' . $conflict['title'] . '<br>' . date('Y.m.d H:i:s', strtotime($conflict['start'])) . ' - '
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
        $xoctUser = xoctUser::getInstance(self::dic()->user());

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        self::dic()->ctrl()->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update(
            self::dic()->ctrl()->getFormAction($this, self::CMD_UPDATE),
            $event->getMetadata(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
    }

    protected function editScheduled(): void
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance(self::dic()->user());

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        self::dic()->ctrl()->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update_scheduled(
            self::dic()->ctrl()->getFormAction($this, self::CMD_UPDATE_SCHEDULED),
            $event->getMetadata(),
            $event->getScheduling(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
    }


    /**
     *
     */
    public function opencaststudio()
    {
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
            . self::dic()->ctrl()->getLinkTarget($this, self::CMD_STANDARD);

        $studio_link .= '?upload.seriesId=' . $this->objectSettings->getSeriesIdentifier()
            . '&return.label=ILIAS'
            . '&return.target=' . urlencode($return_link);
        header('Location:' . $studio_link);
    }


    /**
     *
     */
    public function cut()
    {
        $xoctUser = xoctUser::getInstance(self::dic()->user());
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);

        // check access
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $this->addCurrentUserToProducers();

        // redirect
        $cutting_link = $event->publications()->getCuttingLink();
        header('Location: ' . $cutting_link);
    }

    /**
     * @throws xoctException
     */
    public function download()
    {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_STRING);
        $publication_id = filter_input(INPUT_GET, 'pub_id', FILTER_SANITIZE_STRING);
        $event = $this->event_repository->find($event_id);
        $download_publications = $event->publications()->getDownloadPublications();
        if ($publication_id) {
            $publication = array_filter($download_publications, function ($publication) use ($publication_id) {
                return $publication->getId() == $publication_id;
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
    public function annotate()
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

        header('Location: ' . $annotation_link);
    }


    /**
     *
     */
    public function setOnline()
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $event->getXoctEventAdditions()->setIsOnline(true);
        $event->getXoctEventAdditions()->update();
        $this->cancel();
    }


    /**
     *
     */
    public function setOffline()
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
        self::dic()->ctrl()->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update(
            self::dic()->ctrl()->getFormAction($this, self::CMD_UPDATE),
            $event->getMetadata(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest(self::dic()->http()->request());
        $data = $form->getData();

        $xoctUser = xoctUser::getInstance(self::dic()->user());
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
            return;
        }
        $data = $data[0];

        $this->event_repository->update(new UpdateEventRequest($event->getIdentifier(), new UpdateEventRequestPayload(
            $data['object']
        )));
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     * @throws xoctException|DICException
     */
    protected function updateScheduled()
    {
        $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER, FILTER_SANITIZE_STRING));
        self::dic()->ctrl()->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        // TODO: metadata/scheduling should not be necessary here
        $form = $this->formBuilder->update_scheduled(
            self::dic()->ctrl()->getFormAction($this, self::CMD_UPDATE_SCHEDULED),
            $event->getMetadata(),
            $event->getScheduling(),
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )->withRequest(self::dic()->http()->request());
        $data = $form->getData();

        $xoctUser = xoctUser::getInstance(self::dic()->user());
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }

        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent($this->ui_renderer->render($form));
            return;
        }

        $scheduling = $data['scheduling']['object'] ?? null;
        $this->event_repository->update(new UpdateEventRequest($event->getIdentifier(), new UpdateEventRequestPayload(
            $data['metadata']['object'],
            null,
            $scheduling
        )));
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
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
            $workflow = $this->workflowRepository->getById($workflow_id);
            if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $this->event_repository->find($event_id))
                || is_null($workflow)) {
                ilUtil::sendFailure($this->txt('msg_no_access'), true);
                $this->cancel();
            }
            $request = [
                'event_identifier' => $event_id,
                'workflow_definition_identifier' => $workflow->getWorkflowId(),
            ];
            $params = [];
            foreach (array_filter(explode(',', $workflow->getParameters())) as $param) {
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
    protected function removeInvitations()
    {
        foreach (PermissionGrant::get() as $xoctInvitation) {
            $xoctInvitation->delete();
        }
        ilUtil::sendSuccess($this->txt('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }


    /**
     *
     */
    protected function confirmDelete()
    {
        $event = $this->event_repository->find($_GET[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance(self::dic()->user());
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this));
        if (count($event->publications()->getPublications()) && PluginConfig::getConfig(PluginConfig::F_WORKFLOW_UNPUBLISH)) {
            $header_text = $this->txt('unpublish_confirm');
            $action_text = 'unpublish';
        } else {
            $header_text = $this->objectSettings->getDuplicatesOnSystem() ? $this->txt('delete_confirm_w_duplicates') : $this->txt('delete_confirm');
            $action_text = 'delete';
        }
        $ilConfirmationGUI->setHeaderText($header_text);
        $ilConfirmationGUI->setCancel($this->txt('cancel'), self::CMD_CANCEL);
        $ilConfirmationGUI->setConfirm($this->txt($action_text), self::CMD_DELETE);
        $ilConfirmationGUI->addItem(self::IDENTIFIER, $event->getIdentifier(), $event->getTitle());
        self::dic()->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
    }


    /**
     * @throws DICException
     * @throws xoctException
     */
    protected function delete()
    {
        $event = $this->event_repository->find($_POST[self::IDENTIFIER]);
        $xoctUser = xoctUser::getInstance(self::dic()->user());
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $event, $xoctUser)) {
            ilUtil::sendFailure($this->txt('msg_no_access'), true);
            $this->cancel();
        }
        if (count($event->publications()->getPublications()) && PluginConfig::getConfig(PluginConfig::F_WORKFLOW_UNPUBLISH)) {
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
     * @return bool
     * @throws xoctException
     */
    private function unpublish(Event $event)
    {
        $workflow = PluginConfig::getConfig(PluginConfig::F_WORKFLOW_UNPUBLISH);
        xoctRequest::root()->workflows()->post([
            'workflow_definition_identifier' => $workflow,
            'event_identifier' => $event->getIdentifier()
        ]);
        return true;
    }

    /**
     *
     */
    protected function clearCache()
    {
        CacheFactory::getInstance()->flush();
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     * @return string
     */
    protected function getModalsHTML()
    {
        $modals_html = '';
        foreach ($this->getModals()->getAllComponents() as $modal) {
            $modals_html .= self::dic()->ui()->renderer()->renderAsync($modal);
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
    protected function reportQuality()
    {
        $event = $this->event_repository->find($_POST['event_id']);
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $event)) {
            $message = $this->getQualityReportMessage($event, $_POST['message']);
            $subject = 'ILIAS Opencast Plugin: neue Meldung «Qualitätsprobleme»';

            $report = new Report();
            $report->setType(Report::TYPE_QUALITY)
                ->setUserId(self::dic()->user()->getId())
                ->setSubject($subject)
                ->setMessage($message)
                ->create();
        }
        ilUtil::sendSuccess(self::plugin()->translate('msg_quality_report_sent'), true);
        self::dic()->ctrl()->redirect($this);
    }

    /**
     * @param Event $event
     * @param $message
     * @return string
     */
    protected function getQualityReportMessage(Event $event, $message)
    {
        $link = ilLink::_getStaticLink(
            $_GET['ref_id'],
            ilOpenCastPlugin::PLUGIN_ID,
            true
        );
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
    protected function getDateReportMessage($message)
    {
        $link = ilLink::_getStaticLink($_GET['ref_id'], ilOpenCastPlugin::PLUGIN_ID);
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
    public function txt($key): string
    {
        return self::plugin()->translate('event_' . $key);
    }


    /**
     * @return int
     */
    public function getObjId()
    {
        return $this->objectSettings->getObjId();
    }

    /**
     * @return EventModals
     * @throws DICException
     * @throws ilTemplateException
     */
    public function getModals(): EventModals
    {
        if (is_null($this->modals)) {
            $modals = new EventModals($this, self::plugin()->getPluginObject(), self::dic()->dic(), $this->workflowRepository);
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
        if ($this->objectSettings->getIntroductionText()) {
            $intro = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/tpl.intro.html', '', true, true);
            $intro->setVariable('INTRO', nl2br($this->objectSettings->getIntroductionText()));
            $intro_text = $intro->get();
        }
        return $intro_text;
    }

    protected function createHyperlinks(string $intro_text) : string
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $intro_text, $urls);
        preg_match_all('#\bwww[.][^,\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $intro_text, $urls_www);
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
        $xoctUser = xoctUser::getInstance(self::dic()->user());
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
                $this->seriesRepository->updateACL(new UpdateSeriesACLRequest(
                    $series->getIdentifier(),
                    new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
                ));
                $sleep = true;
            }
        }


        // race condition fix (opencast takes some time to actually update the ACL)
        if ($sleep) {
            sleep(3);
        }
    }
}
