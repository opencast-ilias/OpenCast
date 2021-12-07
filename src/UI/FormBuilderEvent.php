<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\Refinery\Custom\Constraint;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Factory as UIFactory;
use ilMimeTypeUtil;
use ilPlugin;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDFormItemBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingParser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\WorkflowParameterParser;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctConf;

class FormBuilderEvent
{
    private static $accepted_video_mimetypes = [
        ilMimeTypeUtil::VIDEO__AVI,
        ilMimeTypeUtil::VIDEO__QUICKTIME,
        ilMimeTypeUtil::VIDEO__MPEG,
        ilMimeTypeUtil::VIDEO__MP4,
        ilMimeTypeUtil::VIDEO__OGG,
        ilMimeTypeUtil::VIDEO__WEBM,
        ilMimeTypeUtil::VIDEO__X_MS_WMV,
        ilMimeTypeUtil::VIDEO__X_FLV,
        ilMimeTypeUtil::VIDEO__X_MSVIDEO,
        ilMimeTypeUtil::VIDEO__X_DV,
        ilMimeTypeUtil::VIDEO__X_MSVIDEO,
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
        ilMimeTypeUtil::AUDIO__MP4,
        ilMimeTypeUtil::AUDIO__OGG,
        ilMimeTypeUtil::AUDIO__MPEG,
        ilMimeTypeUtil::AUDIO__MPEG3,
        ilMimeTypeUtil::AUDIO__X_AIFF,
        ilMimeTypeUtil::AUDIO__AIFF,
        ilMimeTypeUtil::AUDIO__X_WAV,
        ilMimeTypeUtil::AUDIO__WAV,
        ilMimeTypeUtil::AUDIO__X_MS_WMA,
        ilMimeTypeUtil::AUDIO__BASIC,
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
    private $form_item_builder;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;
    /**
     * @var UploadHandler
     */
    private $uploadHandler;
    /**
     * @var MDParser
     */
    private $MDParser;
    /**
     * @var WorkflowParameterParser
     */
    private $workflowParameterParser;
    /**
     * @var SchedulingParser
     */
    private $schedulingParser;
    /**
     * @var ilPlugin
     */
    private $plugin;


    public function __construct(UIFactory                         $ui_factory,
                                RefineryFactory                   $refinery_factory,
                                MDFormItemBuilder                 $form_item_builder,
                                SeriesWorkflowParameterRepository $workflowParameterRepository,
                                UploadStorageService              $uploadStorageService,
                                UploadHandler                     $uploadHandler,
                                MDParser                          $MDParser,
                                WorkflowParameterParser           $workflowParameterParser,
                                SchedulingParser                  $schedulingParser,
                                ilPlugin                          $plugin)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->form_item_builder = $form_item_builder;
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->uploadStorageService = $uploadStorageService;
        $this->uploadHandler = $uploadHandler;
        $this->MDParser = $MDParser;
        $this->workflowParameterParser = $workflowParameterParser;
        $this->schedulingParser = $schedulingParser;
        $this->plugin = $plugin;
    }

    /**
     * @param string $form_action
     * @param int $obj_id set if the context is a repository object, to use the object level configuration
     * @param bool $as_admin set if the context is a repository object, to use the object level configuration
     * @return Form
     */
    public function upload(string $form_action, int $obj_id = 0, bool $as_admin = false): Form
    {
        $upload_storage_service = $this->uploadStorageService;
        // todo: make required when https://mantis.ilias.de/view.php?id=31645 is fixed
        $file_input = $this->ui_factory->input()->field()->file($this->uploadHandler, 'File')
            ->withAcceptedMimeTypes($this->getMimeTypes())
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($file) use ($upload_storage_service) {
                $id = $file[0];
                return $upload_storage_service->getFileInfo($id);
            }));
        // todo: series selector if obj is 0
        $section = $this->ui_factory->input()->field()->section(
            ['file' => $file_input]
            + $this->form_item_builder->upload()
            + ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormItems()
                : $this->workflowParameterRepository->getFormItemsForObjId($obj_id, $as_admin)),
            $this->plugin->txt('event_create')
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            $workflow_parameter = $this->workflowParameterParser->configurationFromFormData($vs);
            return ['file' => $vs['file'], 'metadata' => $metadata, 'workflow_configuration' => $workflow_parameter];
        }));
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        );
    }

    public function update(string $form_action, Metadata $metadata): Form
    {
        $section = $this->ui_factory->input()->field()->section(
            $this->form_item_builder->edit($metadata),
            $this->plugin->txt('event_edit')
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            return ['metadata' => $metadata];
        }));
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        );
    }

    public function schedule(string $form_action, int $obj_id = 0, bool $as_admin = false): Form
    {
        $section = $this->ui_factory->input()->field()->section(
            $this->form_item_builder->schedule()
            + ['scheduling' => $this->buildSchedulingInput()]
            + ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormItems()
                : $this->workflowParameterRepository->getFormItemsForObjId($obj_id, $as_admin)),
            $this->plugin->txt('event_schedule_new')
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            $workflow_parameter = $this->workflowParameterParser->configurationFromFormData($vs);
            $scheduling = $this->schedulingParser->parseCreateFormData($vs);
            return ['metadata' => $metadata, 'workflow_configuration' => $workflow_parameter, 'scheduling' => $scheduling];
        }))->withAdditionalTransformation($this->buildConstraintStartAfterNow())
            ->withAdditionalTransformation($this->buildConstraintStartAfterNow());
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        );
    }

    public function update_scheduled(string $form_action, Metadata $metadata, Scheduling $scheduling)
    {
        $allow_edit_scheduling = (xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::ALL_METADATA);
        $section = $this->ui_factory->input()->field()->section(
            $this->form_item_builder->editScheduled($metadata, $scheduling)
            + ($allow_edit_scheduling ? $this->buildEditSchedulingInputs($scheduling) : []), $this->plugin->txt('event_edit'))
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) use ($allow_edit_scheduling) {
                $parsed['metadata'] = $this->MDParser->parseFormDataEvent($vs);
                if ($allow_edit_scheduling) {
                    $parsed['scheduling'] = $this->schedulingParser->parseUpdateFormData($vs);
                }
                return $parsed;
            }))->withAdditionalTransformation($this->buildConstraintStartBeforeEnd())
            ->withAdditionalTransformation($this->buildConstraintStartAfterNow());
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        );
    }

    private function buildConstraintStartBeforeEnd(): Constraint
    {
        return $this->refinery_factory->custom()->constraint(function ($vs) {
            /** @var Scheduling $scheduling */
            $scheduling = $vs['scheduling'];
            return $scheduling->getStart()->getTimestamp() < $scheduling->getEnd()->getTimestamp();
        }, $this->plugin->txt('event_msg_end_before_start'));
    }

    private function buildConstraintStartAfterNow(): Constraint
    {
        return $this->refinery_factory->custom()->constraint(function ($vs) {
            /** @var Scheduling $scheduling */
            $scheduling = $vs['scheduling'];
            return $scheduling->getStart()->getTimestamp() > time();
        }, $this->plugin->txt('event_msg_scheduled_in_past'));
    }

    private function buildSchedulingInput(): Input
    {
        $group_no_repeat = $this->ui_factory->input()->field()->group([
            'start_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_start'))
                ->withUseTime(true)->withRequired(true),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_end'))
                ->withUseTime(true)->withRequired(true),
        ], $this->plugin->txt('yes'));
        $group_repeat = $this->ui_factory->input()->field()->group([
            'start_date' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_start'))
                ->withUseTime(false)->withRequired(true),
            'end_date' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_end'))
                ->withUseTime(false)->withRequired(true),
            'weekdays' => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt('event_multiple_weekdays'), [
                'MO' => 'Monday',
                'TU' => 'Tuesday',
                'WE' => 'Wednesday',
                'TH' => 'Thursday',
                'FR' => 'Friday',
                'SA' => 'Saturday',
                'SU' => 'Sunday',
            ])->withRequired(true),
            'start_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_start_time'))
                ->withTimeOnly(true)->withRequired(true),
            'end_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_end_time'))
                ->withTimeOnly(true)->withRequired(true),
        ], $this->plugin->txt('no'));

        return $this->ui_factory->input()->field()->switchableGroup([
            'no_repeat' => $group_no_repeat,
            'repeat' => $group_repeat
        ], $this->plugin->txt('event_multiple'))->withRequired(true)->withValue('no_repeat');

    }

    /**
     * @return Input[]
     */
    private function buildEditSchedulingInputs(Scheduling $scheduling): array
    {
        return [
            'start_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_start'))
                ->withUseTime(true)->withRequired(true)
                ->withValue($scheduling->getStart()->format('Y-m-d H:i:s')),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_end'))
                ->withUseTime(true)->withRequired(true)
                ->withValue($scheduling->getEnd()->format('Y-m-d H:i:s')),
        ];
    }

    private function getMimeTypes(): array
    {
        return xoctConf::getConfig(xoctConf::F_AUDIO_ALLOWED) ?
            self::$accepted_video_mimetypes + self::$accepted_audio_mimetypes
            : self::$accepted_video_mimetypes;
    }

}