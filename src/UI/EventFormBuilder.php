<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use srag\Plugins\Opencast\Util\MimeType as MimeTypeUtil;
use ilPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\Series\SeriesRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use srag\Plugins\Opencast\UI\Scheduling\SchedulingFormItemBuilder;
use srag\Plugins\Opencast\Util\FileTransfer\UploadStorageService;
use ILIAS\UI\Implementation\Component\Input\Field\ChunkedFile;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use DateTimeZone;
use ILIAS\UI\Component\Input\Field\Section;

/**
 * Responsible for creating forms to upload, schedule or edit an event.
 * Delegates stuff to other builders, like MDFormItemBuilder for Metadata fields.
 * One might consider splitting this into smaller builders: one each for upload, schedule and edit.
 */
class EventFormBuilder
{
    public const F_ACCEPT_EULA = 'accept_eula';
    public const MB_IN_B = 1000 * 1000;
    public const DEFAULT_UPLOAD_LIMIT_IN_MIB = 512;

    private static $accepted_video_mimetypes = [
        MimeTypeUtil::VIDEO__AVI,
        MimeTypeUtil::VIDEO__QUICKTIME,
        MimeTypeUtil::VIDEO__MPEG,
        MimeTypeUtil::VIDEO__MP4,
        MimeTypeUtil::VIDEO__OGG,
        MimeTypeUtil::VIDEO__WEBM,
        MimeTypeUtil::VIDEO__X_MS_WMV,
        MimeTypeUtil::VIDEO__X_FLV,
        MimeTypeUtil::VIDEO__X_MSVIDEO,
        MimeTypeUtil::VIDEO__X_DV,
        MimeTypeUtil::VIDEO__X_MSVIDEO,
        'video/mkv',
        'video/x-matroska',
        'video/x-m4v',
        '.mov',
        '.mp4',
        '.m4v',
        '.flv',
        '.mpeg',
        '.avi',
        '.mp4',
        '.mkv'
    ];

    private static $accepted_audio_mimetypes = [
        MimeTypeUtil::AUDIO__MP4,
        MimeTypeUtil::AUDIO__OGG,
        MimeTypeUtil::AUDIO__MPEG,
        MimeTypeUtil::AUDIO__MPEG3,
        MimeTypeUtil::AUDIO__X_AIFF,
        MimeTypeUtil::AUDIO__AIFF,
        MimeTypeUtil::AUDIO__X_WAV,
        MimeTypeUtil::AUDIO__WAV,
        MimeTypeUtil::AUDIO__X_MS_WMA,
        MimeTypeUtil::AUDIO__BASIC,
        'audio/aac',
        'audio/flac',
        'audio/x-m4a',
        '.mp3',
        '.m4a',
        '.wma',
        '.aac',
        '.ogg',
        '.flac',
        '.aiff',
        '.wav',
    ];
    /**
     * @var UIFactory
     */
    protected $ui_factory;
    /**
     * @var RefineryFactory
     */
    private $refinery_factory;
    /**
     * @var MDFormItemBuilder
     */
    private $formItemBuilder;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;
    /**
     * @var UploadHandler|\xoctFileUploadHandlerGUI
     */
    private $uploadHandler;
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var SchedulingFormItemBuilder
     */
    private $schedulingFormItemBuilder;
    /**
     * @var SeriesRepository
     */
    private $seriesRepository;
    /**
     * @var Container
     */
    private $dic;

    public function __construct(
        UIFactory $ui_factory,
        RefineryFactory $refinery_factory,
        MDFormItemBuilder $formItemBuilder,
        SeriesWorkflowParameterRepository $workflowParameterRepository,
        UploadStorageService $uploadStorageService,
        UploadHandler $uploadHandler,
        ilPlugin $plugin,
        SchedulingFormItemBuilder $schedulingFormItemBuilder,
        SeriesRepository $seriesRepository,
        Container $dic
    ) {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->formItemBuilder = $formItemBuilder;
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->uploadStorageService = $uploadStorageService;
        $this->uploadHandler = $uploadHandler;
        $this->plugin = $plugin;
        $this->schedulingFormItemBuilder = $schedulingFormItemBuilder;
        $this->seriesRepository = $seriesRepository;
        $this->dic = $dic;
    }

    /**
     * @param int  $obj_id   set if the context is a repository object, to use the object level configuration
     * @param bool $as_admin set if the context is a repository object, to use the object level configuration
     */
    public function upload(string $form_action, bool $with_terms_of_use, int $obj_id = 0, bool $as_admin = false): Form
    {
        $upload_storage_service = $this->uploadStorageService;
        $factory = $this->ui_factory->input()->field();
        $file_input = ChunkedFile::getInstance(
            $this->uploadHandler,
            $this->plugin->txt('file'),
            $this->plugin->txt('event_supported_filetypes') . ': ' . implode(', ', $this->getAcceptedSuffix())
        )->withRequired(true);
        // Upload Limit
        $configured_upload_limit = (int) PluginConfig::getConfig(PluginConfig::F_CURL_MAX_UPLOADSIZE);
        $upload_limit = $configured_upload_limit > 0
            ? $configured_upload_limit * self::MB_IN_B
            : self::DEFAULT_UPLOAD_LIMIT_IN_MIB * self::MB_IN_B;

        // Chunk Size
        $chunk_size = (int) PluginConfig::getConfig(PluginConfig::F_CURL_CHUNK_SIZE);
        $chunk_size = $chunk_size > 0 ? $chunk_size * 1024 * 1024 : \ilFileUtils::getUploadSizeLimitBytes();

        $file_input = $file_input->withAcceptedMimeTypes($this->getMimeTypes())
                                 ->withRequired(true)
                                 ->withMaxFileSize($upload_limit)
                                 ->withChunkSizeInBytes($chunk_size)
                                 ->withAdditionalTransformation(
                                     $this->refinery_factory->custom()->transformation(
                                         function ($file) use ($upload_storage_service): array {
                                             $id = $file[0] ?? '';
                                             return $upload_storage_service->getFileInfo($id);
                                         }
                                     )
                                 );

        // We must bind the WaitOverlay to an Input since the Form itself is not JS-bindable.
        // We also create the callback function which uses the fileInputMutationObserver to update title.
        $file_input = $file_input->withAdditionalOnLoadCode(
            function ($id) {
                return '
                    il.Opencast.UI.waitOverlay.onFormSubmit("#' . $id . '");
                    let childlist_callback = [];
                    let update_title = (c) => {
                        const targetElement = $(c.targetNode);
                        const titleElement = $("input[data-titleinput]");
                        let last_filename = targetElement.data("last-filename");
                        let title_val = titleElement.val();
                        const selector = ".ui-input-file-input-list .ui-input-file-input .ui-input-file-info span[data-dz-name]";
                        let dz_name_span_find = targetElement.find(selector);
                        if (dz_name_span_find && dz_name_span_find.length > 0) {
                            const dz_name_span = dz_name_span_find[0];
                            const dz_name = dz_name_span.innerText;
                            let filename = dz_name;
                            if (filename && filename.includes(".")) {
                                const filename_split = filename.split(".");
                                const ext = filename_split.pop();
                                filename = filename_split.join(".");
                            }
                            if (title_val == "") {
                                titleElement.val(filename);
                                targetElement.data("last-filename", filename);
                            }
                        } else if (last_filename == title_val) {
                            titleElement.val("");
                            targetElement.data("last-filename", "");
                        }
                    };
                    childlist_callback.push(update_title);
                    il.Opencast.UI.fileInputMutationObserver.init("' . $id . '", childlist_callback);
                ';
            }
        );

        $file_section_inputs = ['file' => $file_input];
        if ($obj_id === 0) {
            $file_section_inputs['isPartOf'] = $this->buildSeriesSelector();
        }
        $file_section = $factory->section(
            $file_section_inputs,
            $this->plugin->txt('file')
        );
        $workflow_param_section = $obj_id == 0 ?
            $this->workflowParameterRepository->getGeneralFormSection($this->plugin->txt('workflow_params_processing_settings'))
            : $this->workflowParameterRepository->getFormSectionForObjId(
                $obj_id,
                $as_admin,
                $this->plugin->txt('workflow_params_processing_settings')
            );
        $inputs = [
            'file' => $file_section,
            'metadata' => $this->formItemBuilder->create_section($as_admin),
        ];
        if (!is_null($workflow_param_section)) {
            $inputs['workflow_configuration'] = $workflow_param_section;
        }
        if ($with_terms_of_use) {
            $inputs[self::F_ACCEPT_EULA] = $this->buildTermsOfUseSection();
        }
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    public function update(string $form_action, Metadata $metadata, bool $as_admin): Form
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$this->formItemBuilder->update_section($metadata, $as_admin)]
        );
    }

    public function schedule(
        string $form_action,
        bool $with_terms_of_use,
        int $obj_id = 0,
        bool $as_admin = false
    ): Form {
        $workflow_param_section = $obj_id == 0 ?
            $this->workflowParameterRepository->getGeneralFormSection($this->plugin->txt('workflow_params_processing_settings'))
            : $this->workflowParameterRepository->getFormSectionForObjId(
                $obj_id,
                $as_admin,
                $this->plugin->txt('workflow_params_processing_settings')
            );
        $inputs = [
            'metadata' => $this->formItemBuilder->schedule_section($as_admin),
            'scheduling' => $this->schedulingFormItemBuilder->create()
        ];
        if (!is_null($workflow_param_section)) {
            $inputs['workflow_configuration'] = $workflow_param_section;
        }
        if ($with_terms_of_use) {
            $inputs[self::F_ACCEPT_EULA] = $this->buildTermsOfUseSection();
        }
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    public function update_scheduled(
        string $form_action,
        Metadata $metadata,
        Scheduling $scheduling,
        bool $as_admin
    ): Form {
        $inputs = ['metadata' => $this->formItemBuilder->update_scheduled_section($metadata, $as_admin)];
        $allow_edit_scheduling = (PluginConfig::getConfig(
            PluginConfig::F_SCHEDULED_METADATA_EDITABLE
        ) == PluginConfig::ALL_METADATA);
        $inputs['scheduling'] = $this->schedulingFormItemBuilder->edit($scheduling, $allow_edit_scheduling);

        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        )->withAdditionalTransformation(
            $this->refinery_factory->custom()->transformation(function ($vs): array {
                $date_field = new MetadataField(MDFieldDefinition::F_START_DATE, MDDataType::datetime());
                $date_field->setValue($vs['scheduling'] ["start_date_time"]);
                $vs['metadata']['object']->addField($date_field);

                $time_field = new MetadataField(MDFieldDefinition::F_START_TIME, MDDataType::time());
                $time_field->setValue(
                    $vs['scheduling'] ["start_date_time"]->setTimeZone(new DateTimeZone('utc'))->format('H:i:s')
                );
                $vs['metadata']['object']->addField($time_field);
                return $vs;
            })
        );
    }

    private function buildTermsOfUseSection(): Section
    {
        return $this->ui_factory->input()->field()->section([
            self::F_ACCEPT_EULA => $this->ui_factory->input()->field()->checkbox(
                $this->plugin->txt('event_accept_eula'),
                PluginConfig::getConfig(PluginConfig::F_EULA)
            )
                                                    ->withRequired(true)
                                                    ->withAdditionalTransformation(
                                                        $this->refinery_factory->custom()->constraint(function ($vs) {
                                                            // must be checked (required-functionality doesn't guarantee that)
                                                            return $vs;
                                                        }, $this->plugin->txt('event_error_alert_accpet_terms_of_use'))
                                                    )
        ], $this->plugin->txt('event_accept_eula'));
    }

    private function getMimeTypes(): array
    {
        return PluginConfig::getConfig(PluginConfig::F_AUDIO_ALLOWED) ?
            array_merge(self::$accepted_video_mimetypes, self::$accepted_audio_mimetypes)
            : self::$accepted_video_mimetypes;
    }

    private function getAcceptedSuffix(): array
    {
        return array_unique(preg_replace(['#video/#', '#audio/#'], '.', $this->getMimeTypes()));
    }

    private function buildSeriesSelector(): \ILIAS\UI\Component\Input\Field\Input
    {
        $xoct_user = xoctUser::getInstance($this->dic->user());
        // fetch early, because acls will be refreshed
        $own_series = $this->seriesRepository->getOwnSeries($xoct_user);
        $series_options = [];
        $user_string = $xoct_user->getUserRoleName() ?? '';
        foreach ($this->seriesRepository->getAllForUser($user_string) as $series) {
            $series_options[$series->getIdentifier()] =
                $series->getMetadata()->getField(MDFieldDefinition::F_TITLE)->getValue()
                . ' (...' . substr($series->getIdentifier(), -4, 4) . ')';
        }

        natcasesort($series_options);
        if (is_null($own_series)) {
            $series_options =
                ['own_series' => $this->seriesRepository->getOwnSeriesTitle($xoct_user)]
                + $series_options;
        }

        return $this->ui_factory
            ->input()
            ->field()
            ->select(
                $this->plugin->txt('event_series'),
                $series_options
            )
            ->withRequired(true);
            /*->withAdditionalTransformation(
                $this->refinery_factory->custom()->constraint(
                    function ($v): bool {
                        return false;
                    },
                    'HELLO WORLD' // error message if no ("-") series is selected. Only works if required is set to false
                )
            )*/
    }
}
