<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ilMimeTypeUtil;
use ilPlugin;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use srag\Plugins\Opencast\UI\Scheduling\SchedulingFormItemBuilder;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctConf;

class EventFormBuilder
{
    const F_ACCEPT_EULA = 'accept_eula';

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
     * @var UploadHandler
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


    public function __construct(UIFactory                         $ui_factory,
                                RefineryFactory                   $refinery_factory,
                                MDFormItemBuilder                 $formItemBuilder,
                                SeriesWorkflowParameterRepository $workflowParameterRepository,
                                UploadStorageService              $uploadStorageService,
                                UploadHandler                     $uploadHandler,
                                ilPlugin                          $plugin,
                                SchedulingFormItemBuilder         $schedulingFormItemBuilder)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->formItemBuilder = $formItemBuilder;
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->uploadStorageService = $uploadStorageService;
        $this->uploadHandler = $uploadHandler;
        $this->plugin = $plugin;
        $this->schedulingFormItemBuilder = $schedulingFormItemBuilder;
    }

    /**
     * @param string $form_action
     * @param bool $with_terms_of_use
     * @param int $obj_id set if the context is a repository object, to use the object level configuration
     * @param bool $as_admin set if the context is a repository object, to use the object level configuration
     * @return Form
     */
    public function upload(string $form_action, bool $with_terms_of_use, int $obj_id = 0, bool $as_admin = false): Form
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
        $file_section = $this->ui_factory->input()->field()->section(
            ['file' => $file_input],
            $this->plugin->txt('file')
        );
        $inputs = [
            'file' => $file_section,
            'metadata' => $this->formItemBuilder->create_section(),
            'workflow_configuration' => ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormSection()
                : $this->workflowParameterRepository->getFormSectionForObjId($obj_id, $as_admin)),
        ];
        if ($with_terms_of_use) {
            $inputs[self::F_ACCEPT_EULA] = $this->buildTermsOfUseSection();
        }
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    public function update(string $form_action, Metadata $metadata): Form
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$this->formItemBuilder->update_section($metadata)]
        );
    }

    public function schedule(string $form_action, bool $with_terms_of_use, int $obj_id = 0, bool $as_admin = false): Form
    {
        $inputs = [
            'metadata' => $this->formItemBuilder->schedule_section(),
            'scheduling' => $this->schedulingFormItemBuilder->create(),
            'workflow_configuration' => ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormSection()
                : $this->workflowParameterRepository->getFormSectionForObjId($obj_id, $as_admin)),
        ];
        if ($with_terms_of_use) {
            $inputs[self::F_ACCEPT_EULA] = $this->buildTermsOfUseSection();
        }
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs);
    }

    public function update_scheduled(string $form_action, Metadata $metadata, Scheduling $scheduling) : Form
    {
        $inputs = ['metadata' => $this->formItemBuilder->update_scheduled_section($metadata)];
        $allow_edit_scheduling = (xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::ALL_METADATA);
        if ($allow_edit_scheduling) {
            $inputs['scheduling'] = $this->schedulingFormItemBuilder->edit($scheduling);
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    private function buildTermsOfUseSection() : Input
    {
        return $this->ui_factory->input()->field()->section([
            self::F_ACCEPT_EULA => $this->ui_factory->input()->field()->checkbox(
                $this->plugin->txt('event_accept_eula'), xoctConf::getConfig(xoctConf::F_EULA))
            ->withRequired(true)
        ], $this->plugin->txt('event_accept_eula'));
    }

    private function getMimeTypes(): array
    {
        return xoctConf::getConfig(xoctConf::F_AUDIO_ALLOWED) ?
            self::$accepted_video_mimetypes + self::$accepted_audio_mimetypes
            : self::$accepted_video_mimetypes;
    }

}