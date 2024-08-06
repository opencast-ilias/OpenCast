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
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\DI\OpencastDIC;
use ILIAS\UI\Component\Input\Field\Section;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;

/**
 * Responsible for creating forms to upload, schedule or edit an event.
 * Delegates stuff to other builders, like MDFormItemBuilder for Metadata fields.
 * One might consider splitting this into smaller builders: one each for upload, schedule and edit.
 */
class EventFormBuilder
{
    use LocaleTrait;

    public const F_ACCEPT_EULA = 'accept_eula';
    public const MB_IN_B = 1000 * 1000;
    public const DEFAULT_UPLOAD_LIMIT_IN_MIB = 512;
    public const F_SUBTITLE_SECTION = 'subtitles';
    public const F_THUMBNAIL_SECTION = 'thumbnail';

    private static array $accepted_video_mimetypes = [
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

    private static array $accepted_audio_mimetypes = [
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
    private \ilOpenCastPlugin $plugin;
    private OpencastDIC $opencast_dic;

    /**
     * @param mixed $refinery_factory
     */
    public function __construct(
        protected UIFactory $ui_factory,
        private RefineryFactory $refinery_factory,
        private MDFormItemBuilder $formItemBuilder,
        private SeriesWorkflowParameterRepository $workflowParameterRepository,
        private UploadStorageService $uploadStorageService,
        private UploadHandler $uploadHandler,
        \ilOpenCastPlugin $plugin,
        private SchedulingFormItemBuilder $schedulingFormItemBuilder,
        private SeriesRepository $seriesRepository,
        private Container $dic
    ) {
        $this->plugin = $plugin;
        $this->opencast_dic = OpencastDIC::getInstance();
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
            fn(string $id): string => '
                    // Wait Overlay.
                    il.Opencast.UI.waitOverlay.onFormSubmit("#' . $id . '");

                    // Thumbnail timepoint validation.
                    $("#' . $id . '").attr("data-videoFileInput", "' . $id . '");

                    // Title Update with filename.
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
                '
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

        // Subtitles.
        $subtitles_enabled = PluginConfig::getConfig(PluginConfig::F_SUBTITLE_UPLOAD_ENABLED) ?? false;
        $accepted_subtitle_mimetypes = PluginConfig::getConfig(PluginConfig::F_SUBTITLE_ACCEPTED_MIMETYPES) ?? [];
        if ($subtitles_enabled && !empty($accepted_subtitle_mimetypes)) {
            $subtitles_section_inputs = [];
            // Get the languages.
            $supported_languages_str = PluginConfig::getConfig(PluginConfig::F_SUBTITLE_LANGS) ?? '';
            $supported_languages_arr = $this->opencast_dic->subtitle_config_form_builder()
                                            ->formattedLanguagesToArray($supported_languages_str);
            foreach ($supported_languages_arr as $lang_code => $lang_name) {
                $no_chunked_upload_handler = clone $this->uploadHandler;
                $no_chunked_upload_handler->toggleChunkedUploadSupport(false);
                $subtitle_file_input = ChunkedFile::getInstance(
                    $no_chunked_upload_handler,
                    $this->getLocaleString('md_lang_list_' . $lang_code, '', $lang_name),
                    $this->plugin->txt('event_supported_filetypes') . ': ' . implode(', ', $accepted_subtitle_mimetypes)
                )->withRequired(false);
                $subtitle_file_input = $subtitle_file_input->withAcceptedMimeTypes($accepted_subtitle_mimetypes)
                ->withRequired(false)
                // Only 1 file per one subtitle is allowed!
                ->withMaxFiles(1)
                ->withMaxFileSize($upload_limit)
                // Setting ChunkSize as upload limit, in order to prevent unwanted chunking.
                ->withChunkSizeInBytes($upload_limit)
                ->withAdditionalTransformation(
                    $this->refinery_factory->custom()->transformation(
                        function ($file) use ($upload_storage_service): array {
                            $id = $file[0] ?? '';
                            return $upload_storage_service->getFileInfo($id);
                        }
                    )
                );

                $subtitles_section_inputs[$lang_code] = $subtitle_file_input;
            }
            // Last check to provide the subtitle section if languages are configured correctly.
            if (!empty($subtitles_section_inputs)) {
                $subtitles_section = $factory->section(
                    $subtitles_section_inputs,
                    $this->plugin->txt('upload_ui_subtitle_section')
                );
                $inputs[self::F_SUBTITLE_SECTION] = $subtitles_section;
            }
        }
        if (!is_null($workflow_param_section)) {
            $inputs['workflow_configuration'] = $workflow_param_section;
        }

        // Thumbnails
        // - First considering the straightToPublishing as a required wf param for this feature to work (by default).
        $wf_id = 'straightToPublishing';
        $stp_wf_value = WorkflowParameter::VALUE_ALWAYS_ACTIVE; // as default value in workflows are always true.
        if (WorkflowParameter::where(['id' => $wf_id])->hasSets()) {
            $workflow_parameter = WorkflowParameter::find($wf_id);
            $stp_wf_value =
                $as_admin ?
                $workflow_parameter->getDefaultValueAdmin() :
                $workflow_parameter->getDefaultValueMember();
        }
        $thumbnail_upload_enabled = PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_UPLOAD_ENABLED) ?? false;
        $accepted_thumbnail_mimetypes = PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_ACCEPTED_MIMETYPES) ?? [];
        $thumbnail_upload_mode = PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_UPLOAD_MODE) ??
            $this->opencast_dic->thumbnail_config_form_builder()::F_THUMBNAIL_UPLOAD_MODE_BOTH;
        // Prepare the mode.
        $thumbnail_upload_mode_is_both =
            $thumbnail_upload_mode == $this->opencast_dic->thumbnail_config_form_builder()::F_THUMBNAIL_UPLOAD_MODE_BOTH;
        $thumbnail_upload_mode_is_file =
            $thumbnail_upload_mode == $this->opencast_dic->thumbnail_config_form_builder()::F_THUMBNAIL_UPLOAD_MODE_FILE;
        $thumbnail_upload_mode_is_timepoint =
            $thumbnail_upload_mode == $this->opencast_dic->thumbnail_config_form_builder()::F_THUMBNAIL_UPLOAD_MODE_TIMEPOINT;

        if ($thumbnail_upload_enabled && !empty($accepted_thumbnail_mimetypes)) {
            $thumbnail_section_inputs = [];
            // Thumbnail file input.
            $thumbnail_file_input = ChunkedFile::getInstance(
                $this->uploadHandler,
                $this->plugin->txt('upload_ui_thumbnail_file'),
                $this->plugin->txt('event_supported_filetypes') . ': ' .
                    implode(', ', array_values($accepted_thumbnail_mimetypes))
            )->withRequired(false);

            $thumbnail_file_input =
                $thumbnail_file_input->withAcceptedMimeTypes(array_values($accepted_thumbnail_mimetypes))
                ->withRequired(false)
                // Only 1 file per one subtitle is allowed!
                ->withMaxFiles(1)
                ->withMaxFileSize($upload_limit)
                // Setting ChunkSize as upload limit, in order to prevent unwanted chunking.
                ->withChunkSizeInBytes($upload_limit)
                ->withAdditionalTransformation(
                    $this->refinery_factory->custom()->transformation(
                        function ($file) use ($upload_storage_service): array {
                            $id = $file[0] ?? '';
                            return $upload_storage_service->getFileInfo($id);
                        }
                    )
                );

            // Thumbnail timepoint timepicker input.
            $target_accept_video_files = implode(',', $this->getMimeTypes());
            $date_default = new \DateTime('today midnight');
            $value_format_str = $date_default->format('H:i:s');
            $timepoint_picker = $factory->dateTime(
                $this->plugin->txt('upload_ui_thumbnail_timepoint'),
                $this->plugin->txt('upload_ui_thumbnail_timepoint_info')
            )
                ->withValue($value_format_str)
                ->withTimeOnly(true)
                ->withAdditionalOnLoadCode(fn(string $id): string => '
                    // On show: Set min date, in order to prevent infinite loop.
                    $("#' . $id . '").on("dp.show", function () {
                        let minDate = new Date();
                        minDate.setHours(0,0,0,0);
                        $("#' . $id . '").data("DateTimePicker").minDate(minDate);
                    });
                    // On change: reset placeholder and the date value if clear is performed.
                    $("#' . $id . '").on("dp.change", function ({date, oldDate}) {
                        if (!date) {
                            // Reset placeholder on clear.
                            $("#' . $id . '").find("input").attr("placeholder", "00:00:00");
                            // Reset value on clear.
                            let resetDate = new Date();
                            resetDate.setHours(0,0,0,0);
                            $("#' . $id . '").data("DateTimePicker").date(resetDate);
                        }
                    });
                    // Extra: set max duration based on uploaded video file.
                    window.URL = window.URL || window.webkitURL;
                    function bindEventExtractVideoDuration() {
                        var file_inputs = $("input:file");
                        if (file_inputs.length > 0) {
                            file_inputs.each(function (i, el) {
                                if ($(el).attr("accept") == "' . $target_accept_video_files . '") {
                                    $(el).on("change" , function () {
                                        let files = this.files;
                                        let video = document.createElement("video");
                                        video.preload = "metadata";
                                        video.onloadedmetadata = function() {
                                            const duration = video.duration;
                                            const hours = parseInt(Math.floor(duration / 3600), 10);
                                            const minutes = parseInt(Math.floor((duration % 3600) / 60), 10);
                                            const remainingSeconds = parseInt(duration % 60, 10);
                                            // Reset date.
                                            const resetDate = new Date();
                                            resetDate.setHours(0,0,0,0);
                                            $("#' . $id . '").data("DateTimePicker").date(resetDate);
                                            // Max date,
                                            const maxDate = new Date();
                                            maxDate.setHours(hours,minutes,remainingSeconds,0);
                                            $("#' . $id . '").data("DateTimePicker").maxDate(maxDate);
                                        }
                                        video.src = URL.createObjectURL(files[0]);
                                    });
                                }
                            });
                        }
                    }
                    setTimeout(function () {
                        bindEventExtractVideoDuration();
                        $("[data-videoFileInput]").find(".ui-input-file-input-dropzone").on("drop", function () {
                            bindEventExtractVideoDuration();
                        });
                        $("[data-videoFileInput]").find(".ui-input-file-input-dropzone > button").on("click", function () {
                            bindEventExtractVideoDuration();
                        });
                    }, 500);')
                ->withAdditionalPickerconfig([
                    'useCurrent' => false,
                    'format' => 'HH:mm:ss',
                ]);

            if ($thumbnail_upload_mode_is_both) {
                $file_input_group = $factory->group(
                    [
                        "file" => $thumbnail_file_input
                    ],
                    $this->plugin->txt('upload_ui_thumbnail_mode_sg_file')
                );
                $timepoint_input_group = $factory->group(
                    [
                        "timepoint" => $timepoint_picker
                    ],
                    $this->plugin->txt('upload_ui_thumbnail_mode_sg_timepoint')
                );

                $switchable_group = $factory->switchableGroup(
                    [
                        'file' => $file_input_group,
                        'timepoint' => $timepoint_input_group
                    ],
                    $this->plugin->txt('upload_ui_thumbnail_mode_sg'),
                    $this->plugin->txt('upload_ui_thumbnail_mode_sg_info')
                );
                $thumbnail_section_inputs['mode'] = $switchable_group;
            }
            // Thumbnail File.
            if ($thumbnail_upload_mode_is_file) {
                $thumbnail_section_inputs['file'] = $thumbnail_file_input;
            }

            // Timepoint
            if ($thumbnail_upload_mode_is_timepoint) {
                $thumbnail_section_inputs['timepoint'] = $timepoint_picker;
            }

            if (!empty($thumbnail_section_inputs)) {
                $thumbnail_section_info = '';
                if ($stp_wf_value == WorkflowParameter::VALUE_SHOW_IN_FORM
                    || $stp_wf_value == WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET) {
                    $thumbnail_section_info = $this->plugin->txt('upload_ui_thumbnail_section_stp_info');
                } elseif ($stp_wf_value == WorkflowParameter::VALUE_ALWAYS_INACTIVE) {
                    $thumbnail_section_info = $this->plugin->txt('upload_ui_thumbnail_section_stp_disabled_info');
                }

                $thumbnail_section = $factory->section(
                    $thumbnail_section_inputs,
                    $this->plugin->txt('upload_ui_thumbnail_section'),
                    $thumbnail_section_info
                )->withDisabled($stp_wf_value == WorkflowParameter::VALUE_ALWAYS_INACTIVE);
                $inputs[self::F_THUMBNAIL_SECTION] = $thumbnail_section;
            }
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
            $this->refinery_factory->custom()->transformation(function (array $vs): array {
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
                                                        $this->refinery_factory->custom()->constraint(fn($vs) =>
                                                            // must be checked (required-functionality doesn't guarantee that)
                                                            $vs, $this->plugin->txt('event_error_alert_accpet_terms_of_use'))
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
