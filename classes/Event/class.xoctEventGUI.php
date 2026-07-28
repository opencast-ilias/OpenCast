<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Views\Event\CreateEvent;
use srag\Plugins\Opencast\Model\Publication\Attachment;
use srag\Plugins\Opencast\Model\Publication\Media;
use srag\Plugins\Opencast\Model\Publication\Publication;
use ILIAS\UI\Implementation\DefaultRenderer;
use ILIAS\DI\UIServices;
use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use srag\Plugins\Opencast\API\OpencastAPI;
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
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;
use srag\Plugins\Opencast\UI\EventFormBuilder;
use srag\Plugins\Opencast\UI\EventTableBuilder;
use srag\Plugins\Opencast\UI\Modal\EventModals;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use srag\Plugins\Opencast\Util\Player\PaellaConfigServiceFactory;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Util\OutputResponse;
use srag\Plugins\Opencast\Container\Init;
use srag\Plugins\Opencast\UI\Integration\Integration;
use srag\Plugins\Opencast\Views\Series\Display;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameter;
use ILIAS\UI\Renderer;

/**
 * Class xoctEventGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_Calls      xoctEventGUI: xoctPlayerGUI
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI
{
    use OutputResponse;

    public const IDENTIFIER = EventActionParameter::EVENT_ID->value;
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
    public const CMD_START_WORKFLOW = 'startWorkflow';
    public const CMD_OPENCAST_STUDIO = 'opencaststudio';
    public const CMD_SELECT_DOWNLOAD = 'selectDownload';
    public const CMD_DOWNLOAD = 'download';
    public const CMD_CREATE_SCHEDULED = 'createScheduled';
    public const CMD_EDIT_SCHEDULED = 'editScheduled';
    public const CMD_UPDATE_SCHEDULED = 'updateScheduled';
    public const CMD_REPORT_QUALITY_MODAL = 'reportQualityModal';
    public const CMD_REPORT_DATE_MODAL = 'reportDateModal';
    public const CMD_START_WORKFLOW_MODAL = 'startWorkflowModal';
    private \WaitOverlay $wait_overlay;
    /**
     * @var Services
     */
    private object $cache;
    private int $ref_id;
    private Integration $ui_integration;
    /**
     * @var DefaultRenderer
     */
    protected $custom_renderer;
    /**
     * @var EventModals|null
     */
    protected $modals;
    private Renderer $ui_renderer;
    private Container $dic;
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
     * @var UIServices
     */
    private $ui;

    public function __construct(
        private \ilObjOpenCastGUI $parent_gui,
        protected ObjectSettings $objectSettings,
        protected EventRepository $event_repository,
        private EventFormBuilder $formBuilder,
        private EventTableBuilder $eventTableBuilder,
        private WorkflowRepository $workflowRepository,
        private ACLUtils $ACLUtils,
        private SeriesRepository $seriesRepository,
        /**
         * @var xoctFileUploadHandlerGUI
         */
        private UploadHandler $uploadHandler,
        private PaellaConfigStorageService $paellaConfigStorageService,
        private PaellaConfigServiceFactory $paellaConfigServiceFactory,
        Container $dic
    ) {
        global $DIC;
        $opencastContainer = Init::init();
        parent::__construct();

        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->ui = $DIC->ui();
        $this->dic = $dic;
        $this->ui_renderer = $this->ui->renderer();
        $this->ui_integration = $opencastContainer[Integration::class];
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
        $this->main_tpl->addJavaScript($this->plugin->getRelativeDirectory() . '/js/opencast/dist/index.js');

        $this->main_tpl->addCss(
            $this->plugin->getRelativeDirectory() . '/templates/default/reporting_modal.css'
        );

        // Start Workflow stylesheet
        $this->main_tpl->addCss(
            $this->plugin->getRelativeDirectory() . '/templates/default/startworkflow_modal.css'
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
        // add "add" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_add_new');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
            $b->setPrimary(true);
            $this->toolbar->addButtonInstance($b);
        }

        // add "schedule" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SCHEDULE_EVENT) && PluginConfig::getConfig(
            PluginConfig::F_CREATE_SCHEDULED_ALLOWED
        )) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_schedule_new');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SCHEDULE));
            $b->setPrimary(true);
            $this->toolbar->addButtonInstance($b);
        }

        // add "Opencast Studio" button
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_RECORD_EVENT) && PluginConfig::getConfig(
            PluginConfig::F_STUDIO_ALLOWED
        )) {
            $b = ilLinkButton::getInstance();
            $b->setCaption('rep_robj_xoct_event_opencast_studio');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_OPENCAST_STUDIO));
            $b->setPrimary(true);
            $b->setTarget('_blank');
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
    }

    protected function index(): void
    {
        // This part is only needed for legacy resons, see later
        $event_modals = new EventModals($this, $this->plugin, $this->dic, $this->workflowRepository);
        $event_modals->initReportDate();
        $modal = $event_modals->getReportDateModal();

        // Main Content (new Approach): The Series Display.
        $display_series = new Display(
            $this->ui,
            $this->ui_integration,
            $this->objectSettings
        );

        $this->main_tpl->setContent(
            $this->ui_renderer->render(
                array_filter(array_merge($display_series->get(), [$modal]))
            )
        );

        // Report Date Modification Modal: This is a absolute mess... The Button ist added anyway in prepareContent, but "hidden".
        // Only if the Series has sceduled events, it gets shown via js.
        // We changed that for now but this whole "eventModals" things must be refactored as soon as possible.

        if (
            $modal
            && $display_series->hasScheduledEvents()
            && ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE)
        ) {

            $button = $this->ui->factory()->button()->standard(
                $this->txt('report_date_modification'),
                '#'
            )->withOnClick($modal->getShowSignal());

            $this->toolbar->addComponent($button);
        }
    }

    protected function add(): void
    {
        $pre_form_data = $this->parent_gui->renderLinksListSection();
        if (!empty($pre_form_data)) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
        }

        $upload = new CreateEvent(
            $this->ctrl->getFormAction($this, self::CMD_CREATE),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );

        $this->main_tpl->setContent(
            $pre_form_data . $this->ui_renderer->render(
                $upload->get()
            )
        );
    }

    protected function create(): void
    {
        $extra_workflow_params = new \stdClass();
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
            $pre_form_data = $this->parent_gui->renderLinksListSection();
            if (!empty($pre_form_data)) {
                $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
            }
            $this->main_tpl->setContent($pre_form_data . $this->ui_renderer->render($form));
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

        // Thumbnail
        $thumbnail_upload_enabled = PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_UPLOAD_ENABLED) ?? false;
        $thumbnail_file = null;
        $thumbnail_file_id = null;
        $thumbnail_timepoint = null;
        if ($thumbnail_upload_enabled && isset($data[EventFormBuilder::F_THUMBNAIL_SECTION])) {
            $thumbnail_section_data = $data[EventFormBuilder::F_THUMBNAIL_SECTION];

            // Both mode.
            if (isset($thumbnail_section_data['mode'])) {
                if ($thumbnail_section_data['mode'][0] == 'file' &&
                    !empty($thumbnail_section_data['mode'][1]['file']['id'])) {
                    $thumbnail_file_id = $thumbnail_section_data['mode'][1]['file']['id'];
                    $thumbnail_file = xoctUploadFile::getInstanceFromFileArray(
                        $thumbnail_section_data['mode'][1]['file']
                    );
                }

                if ($thumbnail_section_data['mode'][0] == 'timepoint' &&
                    $thumbnail_section_data['mode'][1]['timepoint'] instanceof \DateTimeImmutable) {
                    $thumbnail_timepoint = $thumbnail_section_data['mode'][1]['timepoint'];
                }
            }

            // File Upload mode.
            if (isset($thumbnail_section_data['file']) &&
                !empty($thumbnail_section_data['file']['id'])) {
                $thumbnail_file_id = $thumbnail_section_data['file']['id'];
                $thumbnail_file = xoctUploadFile::getInstanceFromFileArray($thumbnail_section_data['file']);
            }

            // Timepoint mode.
            if (isset($thumbnail_section_data['timepoint']) &&
                $thumbnail_section_data['timepoint'] instanceof \DateTimeImmutable) {
                $thumbnail_timepoint = $thumbnail_section_data['timepoint'];
            }

            // Taking care of file.
            if (!empty($thumbnail_file)) {
                $extra_workflow_params->withUploadedThumbnail = "true";
            }

            // Taking care of timepoint here and put it in the workflow configuration already.
            if (!empty($thumbnail_timepoint)) {
                $formatted_timepoint = $thumbnail_timepoint->format('H:i:s');
                $timepoint_seconds = strtotime($formatted_timepoint) - strtotime('TODAY');
                if ($timepoint_seconds > 0) {
                    $extra_workflow_params->snapshotThumbnailTime = (string) $timepoint_seconds;
                }
            }
        }

        // Subtitles.
        $subtitles = [];
        $subtitle_file_ids = [];
        if (!empty($data[EventFormBuilder::F_SUBTITLE_SECTION])) {
            foreach ($data[EventFormBuilder::F_SUBTITLE_SECTION] as $lang_code => $subtitle_file) {
                if (!empty($subtitle_file['id'])) { // Make sure the file is not empty by checking the id!
                    $subtitles[$lang_code] = xoctUploadFile::getInstanceFromFileArray($subtitle_file);
                    $subtitle_file_ids[] = $subtitle_file['id'];
                }
            }
        }

        $this->event_repository->upload(
            new UploadEventRequest(
                new UploadEventRequestPayload(
                    $metadata,
                    $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance($this->user)),
                    new Processing(
                        PluginConfig::getConfig(PluginConfig::F_WORKFLOW),
                        $this->getDefaultWorkflowParameters(
                            $data['workflow_configuration']['object'] ?? null,
                            $extra_workflow_params
                        )
                    ),
                    xoctUploadFile::getInstanceFromFileArray($data['file']['file']),
                    $subtitles,
                    $thumbnail_file
                )
            )
        );
        $this->uploadHandler->getUploadStorageService()->delete($data['file']['file']['id']);
        // Get rid of thumbnail.
        if (!empty($thumbnail_file_id)) {
            $this->uploadHandler->getUploadStorageService()->delete($thumbnail_file_id);
        }
        // Removing subtitle files afterwards.
        foreach ($subtitle_file_ids as $id) {
            $this->uploadHandler->getUploadStorageService()->delete($id);
        }

        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    public function getDefaultWorkflowParameters(
        ?\stdClass $fromData = null,
        ?\stdClass $extraParameters = null
    ): \stdClass {
        $WorkflowParameter = new WorkflowParameter();
        $defaultParameter = $fromData ?? new stdClass();
        $admin = ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS);
        foreach ($WorkflowParameter::get() as $param) {
            $id = $param->getId();
            $defaultValue = $admin ? $param->getDefaultValueAdmin() : $param->getDefaultValueMember();
            if (isset($fromData->{$id})) {
                continue;
            }
            if ($defaultValue != WorkflowParameter::VALUE_ALWAYS_ACTIVE) {
                continue;
            }
            $defaultParameter->{$id} = "true";
        }

        // Append extra parameters.
        if (!empty($extraParameters)) {
            foreach ($extraParameters as $key => $value) {
                $defaultParameter->{$key} = $value;
            }
        }

        return $defaultParameter;
    }

    protected function schedule(): void
    {
        $pre_form_data = $this->parent_gui->renderLinksListSection();
        if (!empty($pre_form_data)) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            $this->ctrl->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );
        $this->main_tpl->setContent($pre_form_data . $this->ui_renderer->render($form));
    }

    protected function createScheduled(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SCHEDULE_EVENT)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $pre_form_data = $this->parent_gui->renderLinksListSection();
        if (!empty($pre_form_data)) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates_events'));
        }
        $form = $this->formBuilder->schedule(
            $this->ctrl->getFormAction($this, self::CMD_CREATE_SCHEDULED),
            !ToUManager::hasAcceptedToU($this->user->getId()),
            $this->objectSettings->getObjId(),
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        )->withRequest($this->http->request());
        $data = $form->getData();

        if (!$data) {
            $this->main_tpl->setContent($pre_form_data . $this->ui_renderer->render($form));
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
            $this->main_tpl->setContent($pre_form_data . $this->ui_renderer->render($form));
            return;
        }

        $this->main_tpl->setOnScreenMessage('success', $this->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($pre_form_data . $this->ui_renderer->render($form));
    }

    private function checkAndShowConflictMessage(xoctException $e): void
    {
        if ($e->getCode() === xoctException::API_CALL_STATUS_409) {
            $conflicts = (array) json_decode(substr($e->getMessage(), 10), true);
            $message = $this->txt('msg_scheduling_conflict') . '<br>';
            foreach ($conflicts as $conflict) {
                $message .= '<br>' . $conflict['title'] . '<br>' . date(
                    'Y.m.d H:i:s',
                    strtotime((string) $conflict['start'])
                ) . ' - '
                    . date('Y.m.d H:i:s', strtotime((string) $conflict['end'])) . '<br>';
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
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
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
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );
        $this->main_tpl->setContent($this->ui_renderer->render($form));
    }

    public function opencaststudio(): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_RECORD_EVENT)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }
        // Consider studio group as default.
        $user_group_name = PluginConfig::F_GROUP_STUDIO;

        // Looking for "Edit Video" permission, to add user to producers group.
        if (ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)) {
            $user_group_name = PluginConfig::F_GROUP_PRODUCERS;
        }

        $this->addCurrentUserToGroup($user_group_name);

        // redirect to oc studio
        $base = rtrim((string) PluginConfig::getConfig(PluginConfig::F_API_BASE), "/");
        $base = str_replace('/api', '', $base);

        $studio_link = $base . '/studio';

        // get the custom url for the studio.
        $custom_url = PluginConfig::getConfig(PluginConfig::F_STUDIO_URL);
        if (!empty($custom_url)) {
            $studio_link = rtrim((string) $custom_url, "/");
        }

        // First put the params in an associative array, in order to have a clearer understanding of what is going on!
        $query_params = [
            'upload.seriesId' => $this->objectSettings->getSeriesIdentifier(),
            'upload.seriesField' => 'hidden',
        ];

        // The return label and return target parameters for Opencast Studio are temporarily disabled due to an issue.
        // For more details, see: https://github.com/opencast-ilias/OpenCast/issues/424#issuecomment-3024202623

        /* $return_link = ILIAS_HTTP_PATH . '/'
            . $this->ctrl->getLinkTarget($this, self::CMD_STANDARD);

        $query_params['return.label'] = 'ILIAS';
        $query_params['return.target'] = urlencode($return_link); */

        // Get the base ACL of the user.
        $acls = $this->ACLUtils->getBaseACLForUser(xoctUser::getInstance($this->user));

        // Convert the ACL to Studio ACL notation string.
        $studio_acl_notation = (new ACLtoXML($acls))->getStudioACLObjectNotation();

        // If ACL notation is not empty, add it to the query parameters array.
        if (!empty($studio_acl_notation)) {
            $query_params['upload.acl'] = $studio_acl_notation;
        }

        // Sort the query parameters array in reverse order to ensure that the parameters are in the correct order.
        krsort($query_params);

        // Combine the query parameters array into a single query string.
        $combined_query_string = implode(
            '&',
            array_map(fn($k, $v): string => "$k=$v", array_keys($query_params), array_values($query_params))
        );

        // Append the query string to the studio link.
        $studio_link .= '?' . $combined_query_string;

        if (empty(PluginConfig::getConfig(PluginConfig::F_JWT_SECURITY_ENABLED))) {
            $this->ctrl->redirectToURL($studio_link);
            return;
        }

        $encoded_studio_link = $base . '/studio?' . http_build_query($query_params);

        $jwt = $this->api->issueExternalServicesJwtFor(OpencastAPI::JWT_SERVICE_STUDIO);
        if (empty($jwt)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Unable to provide a JWT for Studio service!'
            );
        }
        $redirect_template_html = $this->getJwtRedirectHtml($jwt, $encoded_studio_link);
        $this->main_tpl->setContent($redirect_template_html);
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

        $this->addCurrentUserToGroup();

        // Cutting and processing happens later in the external editor, so ILIAS never
        // sees the state change itself. Drop the cached (still SUCCEEDED) event now, so
        // a plain browser reload after "Save and process changes" re-fetches the event
        // and shows the "Converting" status instead of the stale cached one (see #540).
        $this->event_repository->invalidateCache($event->getIdentifier());

        // redirect
        $cutting_link = $event->publications()->getCuttingLink();

        if (empty(PluginConfig::getConfig(PluginConfig::F_JWT_SECURITY_ENABLED))) {
            $this->ctrl->redirectToURL($cutting_link);
            return;
        }

        $jwt = $this->api->issueExternalServicesJwtFor(OpencastAPI::JWT_SERVICE_EDITOR);
        if (empty($jwt)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Unable to provide a JWT for Editor service!'
            );
        }
        $redirect_template_html = $this->getJwtRedirectHtml( $jwt, $cutting_link);
        $this->main_tpl->setContent($redirect_template_html);
    }

    /**
     * Generates HTML for JWT-based redirect from the template.
     *
     * This method renders a redirect template by populating it with the provided redirect URL,
     * JWT token, and target URL. The resulting HTML is used to automatically redirect the user
     * to the target link using the JWT for authentication.
     *
     * @param string $jwt          The JWT token for authentication
     * @param string $target_link  The final target URL to redirect to after JWT validation
     * @return string The generated HTML string for the redirect page
     */
    private function getJwtRedirectHtml(string $jwt, string $target_link): string
    {
        $base = rtrim((string) PluginConfig::getConfig(PluginConfig::F_API_BASE), "/");
        $redirect_url = str_replace('/api', '/redirect/get', $base);
        $redirect_template = $this->plugin->getTemplate('default/tpl.jwt_redirect.html', false, false);
        $redirect_template->setVariable('ACTION', $redirect_url);
        $redirect_template->setVariable('JWT', $jwt);
        $redirect_template->setVariable('TARGET_URL', $target_link);
        return $redirect_template->get();
    }

    private function retrieveQuery(string $q): ?string
    {
        return $this->http->request()->getQueryParams()[$q] ?? null;
    }

    public function selectDownload(): void
    {
        $this->ctrl->saveParameter($this, self::IDENTIFIER);

        $modal = $this
            ->ui_integration
            ->events()
            ->publications()
            ->asListInModal(
                $this->http->request()->getQueryParams()[self::IDENTIFIER],
                $this->ctrl->getLinkTarget($this, self::CMD_DOWNLOAD)
            );

        $this->outAsync($modal);
    }

    public function download(): void
    {
        $event_id = $this->retrieveQuery(self::IDENTIFIER);
        $publication_id = $this->retrieveQuery('pub_id');
        $usage_type = $this->retrieveQuery('usage_type');
        $usage_id = $this->retrieveQuery('usage_id');
        $event = $this->event_repository->find($event_id);
        // Check permission to download before anything else.
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(
            ilObjOpenCastAccess::ACTION_DOWNLOAD_EVENT,
            $event,
            $xoctUser,
            $this->objectSettings
        )) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_access'), true);
            $this->cancel();
        }

        $download_publications = $event->publications()->getDownloadPublications();
        // Now that we have multiple sub-usages, we first check for publication_id which is passed by the multi-dropdowns.
        if ($publication_id) {
            $publication = array_filter(
                $download_publications,
                fn(Attachment|Media|Publication $publication): bool => $publication->getId() === $publication_id
            );
            $publication = reset($publication);
        } elseif (!empty($usage_type) && !empty($usage_id)) {
            // If this is not multi-download dropdown, then it has to have the usage_type and usage_id parameters identified.
            $publication = array_filter(
                $download_publications,
                fn(
                    Attachment|Media|Publication $publication
                ): bool => $publication->usage_id == $usage_id && $publication->usage_type === $usage_type
            );
            $publication = reset($publication);
        } else {
            // As a fallback we take out the last publication, if non of the above has been met!
            $publication = reset($download_publications);
        }

        if (empty($publication)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->txt('msg_no_download_publication'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }

        $url = $publication->getUrl();
        $extension = pathinfo($url)['extension'] ?? null;
        $url = PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS) ? xoctSecureLink::signDownload($url) : $url;

        // if (PluginConfig::getConfig(PluginConfig::F_EXT_DL_SOURCE)) {
        if (property_exists($publication, 'ext_dl_source') && $publication->ext_dl_source) {
            // Open external source page
            header('Location: ' . $url);
        } else {
            $file_name = $event->getTitle() . ($extension !== null ? '.' . $extension : '');
            // Stream the file straight from Opencast to the client instead of buffering the whole
            // file in PHP memory (the previous file_get_contents() failed for files > memory_limit
            // and timed out behind proxies). Nothing is stored locally; the remote response is
            // passed through in constant-memory chunks.
            $size = method_exists($publication, 'getSize') ? (int) $publication->getSize() : 0;
            $this->streamRemoteToClient($url, $file_name, $publication->getMediatype(), $size);
        }

        $this->closeResponse();
    }

    /**
     * Streams a remote file (Opencast publication URL) directly through to the client.
     *
     * The remote response is forwarded in constant-memory chunks via a cURL write callback;
     * the file is never fully buffered in PHP memory nor written to a local temp file. This
     * replaces the former echo file_get_contents($url), which loaded the whole file into a
     * single PHP string and therefore failed for files larger than memory_limit (and was prone
     * to proxy buffering/timeouts).
     *
     * Note: ILIAS\FileDelivery / the HTTP ResponseSender cannot be used here because both rewind()
     * the body stream, which is not possible on a non-seekable remote stream, and the
     * X-Sendfile/X-Accel builders expect a local file path.
     *
     * @param int $size Known file size in bytes (0 if unknown) to emit a Content-Length header.
     */
    private function streamRemoteToClient(
        string $url,
        string $file_name,
        ?string $mime_type,
        int $size = 0
    ): void {
        // Drop any active output buffering so chunks are flushed and memory stays flat.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Large files may take a while to stream.
        set_time_limit(0);

        header('Content-Type: ' . ($mime_type !== null && $mime_type !== '' ? $mime_type : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        if ($size > 0) {
            header('Content-Length: ' . $size);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 256 * 1024);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, string $chunk): int {
            echo $chunk;
            flush();
            return strlen($chunk);
        });
        curl_exec($ch);
        curl_close($ch);
    }

    public function annotate(): void
    {
        $event = $this->event_repository->find($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        // check access
        if (ilObjOpenCastAccess::hasPermission(
            ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS
        ) || ilObjOpenCastAccess::hasWriteAccess()) {
            $this->addCurrentUserToGroup();
        }

        // redirect
        $annotation_link = $event->publications()->getAnnotationLink(
            $this->ref_id
        );

        if (empty(PluginConfig::getConfig(PluginConfig::F_JWT_SECURITY_ENABLED))) {
            $this->ctrl->redirectToURL($annotation_link);
            return;
        }

        $jwt = $this->api->issueExternalServicesJwtFor(OpencastAPI::JWT_SERVICE_ANNOTATION_TOOL, $event->getIdentifier());
        if (empty($jwt)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Unable to provide a JWT for Annotation-tool service!'
            );
        }
        $redirect_template_html = $this->getJwtRedirectHtml($jwt, $annotation_link);
        $this->main_tpl->setContent($redirect_template_html);
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
        $event = $this->event_repository->find($this->retrieveQuery(self::IDENTIFIER));
        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        $form = $this->formBuilder->update(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE),
            $event->getMetadata(),
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
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
        $event = $this->event_repository->find($this->retrieveQuery(self::IDENTIFIER));
        $this->ctrl->setParameter($this, self::IDENTIFIER, $event->getIdentifier());
        // TODO: metadata/scheduling should not be necessary here
        $form = $this->formBuilder->update_scheduled(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE_SCHEDULED),
            $event->getMetadata(),
            $event->getScheduling(),
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
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
        try {
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
        } catch (xoctException $e) {
            $this->checkAndShowConflictMessage($e);
            $this->main_tpl->setContent($this->ui_renderer->render($form));
            return;
        }

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
            $event_id = strip_tags($post_body['startworkflow_event_id']);
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
            $default_configs = [];
            $config_panel_html_array = $this->workflowRepository->getConfigPanelAsArrayById($workflow_id);
            $config_panel_json_array = $this->workflowRepository->getConfigPanelJsonAsArrayById($workflow_id);
            $default_configs = array_merge($config_panel_html_array, $config_panel_json_array);
            $configurations = [];

            foreach ($default_configs as $key => $config_data) {
                $value = $config_data['value'];
                $type = $config_data['type'];
                if (in_array($key, array_keys($received_configs), true)) {
                    $received_value = $received_configs[$key];
                    // Take care of datetime conversion.
                    if (str_contains((string) $type, 'datetime')) {
                        $datetime = new DateTimeImmutable($received_value);
                        $received_value = $datetime->format('Y-m-d\TH:i:s\Z');
                        $value = $received_value;
                    } elseif ($type == 'text') {
                        $value = strip_tags((string) $received_value);
                    } elseif ($type == 'number') {
                        $value = intval($received_value);
                    } else {
                        $value = $received_value;
                    }
                } elseif ($type === 'checkbox') {
                    // This means that the checkbox is not checked.
                    $value = false;
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
            // The workflow moves the event into processing on Opencast; drop the cached
            // SUCCEEDED state so the reload shows the "Converting" status (see #540).
            $this->event_repository->invalidateCache($event_id);
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
        $pre_form_data = $this->parent_gui->renderLinksListSection();
        $this->main_tpl->setContent($pre_form_data . $ilConfirmationGUI->getHTML());
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
                // The unpublish workflow moves the event into processing on Opencast;
                // drop the cached SUCCEEDED state so the reload reflects it (see #540).
                $this->event_repository->invalidateCache($event->getIdentifier());
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

    protected function reportDateModal(): void
    {
        $this->ctrl->saveParameter($this, self::IDENTIFIER);
        $event_modals = new EventModals($this, $this->plugin, $this->dic, $this->workflowRepository);
        $event_modals->initReportDate($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        $modal = ($event_modals)->getReportDateModal();
        $this->outAsync($modal);
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

    protected function startWorkflowModal(): void
    {
        $this->ctrl->saveParameter($this, self::IDENTIFIER);
        $event_modals = new EventModals($this, $this->plugin, $this->dic, $this->workflowRepository);
        $event_modals->initWorkflows($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        $modal = ($event_modals)->getStartworkflowModal();
        $this->outAsync($modal);
    }

    protected function reportQualityModal(): void
    {
        $this->ctrl->saveParameter($this, self::IDENTIFIER);
        $event_modals = new EventModals($this, $this->plugin, $this->dic, $this->workflowRepository);
        $event_modals->initReportQuality($this->http->request()->getQueryParams()[self::IDENTIFIER]);

        $modal = $event_modals->getReportQualityModal();
        $this->outAsync($modal);
    }

    protected function reportQuality(): void
    {
        $event = $this->event_repository->find($this->http->request()->getParsedBody()[self::IDENTIFIER]);
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

    public function txt(string $key): string
    {
        return $this->plugin->txt('event_' . $key);
    }

    public function getObjId(): int
    {
        return $this->objectSettings->getObjId();
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

    /**
     * Adds the current user to the specified group.
     * Producers group is the default selected group. (PluginConfig::F_GROUP_PRODUCERS)
     *
     * @param string $group_config_name the group config name (default PluginConfig::F_GROUP_PRODUCERS)
     *
     */
    protected function addCurrentUserToGroup(string $group_config_name = PluginConfig::F_GROUP_PRODUCERS): void
    {
        $xoctUser = xoctUser::getInstance($this->user);
        // add user to the group
        $sleep = false;
        try {
            $group_config_value = PluginConfig::getConfig($group_config_name);
            if (!empty($group_config_value)) {
                $group_obj = Group::find($group_config_value);
                $sleep = $group_obj->addMember($xoctUser);
            }
        } catch (xoctException) {
        }

        // Extra things to do for producers group.
        // add user to series producers
        if ($group_config_name === PluginConfig::F_GROUP_PRODUCERS && $this->objectSettings->getSeriesIdentifier(
        ) !== null) {
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
