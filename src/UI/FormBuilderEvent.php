<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDFormItemBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\WorkflowParameterParser;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctException;

class FormBuilderEvent
{
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


    public function __construct(UIFactory                         $ui_factory,
                                RefineryFactory                   $refinery_factory,
                                MDFormItemBuilder                 $form_item_builder,
                                SeriesWorkflowParameterRepository $workflowParameterRepository,
                                UploadStorageService              $uploadStorageService,
                                UploadHandler                     $uploadHandler,
                                MDParser                          $MDParser,
                                WorkflowParameterParser           $workflowParameterParser,
                                SchedulingParser                  $schedulingParser)
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
    }

    /**
     * @param string $form_action
     * @param int $obj_id set if the context is a repository object, to use the object level configuration
     * @param bool $as_admin set if the context is a repository object, to use the object level configuration
     * @return Form
     * @throws xoctException
     */
    public function buildUploadForm(string $form_action, int $obj_id = 0, bool $as_admin = false): Form
    {
        $upload_storage_service = $this->uploadStorageService;
        // todo: make required when https://mantis.ilias.de/view.php?id=31645 is fixed
        $file_input = $this->ui_factory->input()->field()->file($this->uploadHandler, 'File')
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($file) use ($upload_storage_service) {
                $id = $file[0];
                return $upload_storage_service->getFileInfo($id);
            }));
        // todo: series selector if obj is 0
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            ['file' => $file_input]
            + $this->form_item_builder->upload()
            + ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormItems()
                : $this->workflowParameterRepository->getFormItemsForObjId($obj_id, $as_admin))
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            $workflow_parameter = $this->workflowParameterParser->configurationFromFormData($vs);
            return ['file' => $vs['file'], 'metadata' => $metadata, 'workflow_configuration' => $workflow_parameter];
        }));
    }

    public function buildUpdateForm(string $form_action, Metadata $metadata): Form
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $this->form_item_builder->edit($metadata)
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            return ['metadata' => $metadata];
        }));
    }

    public function buildScheduleForm(string $form_action, int $obj_id = 0, bool $as_admin = false): Form
    {
        // TODO: contraints for date
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $this->form_item_builder->schedule()
            + ['scheduling' => $this->buildSchedulingInput()]
            + ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormItems()
                : $this->workflowParameterRepository->getFormItemsForObjId($obj_id, $as_admin))
        )->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
            $metadata = $this->MDParser->parseFormDataEvent($vs);
            $workflow_parameter = $this->workflowParameterParser->configurationFromFormData($vs);
            $scheduling = $this->schedulingParser->parseCreateFormData($vs);
            return ['metadata' => $metadata, 'workflow_configuration' => $workflow_parameter, 'scheduling' => $scheduling];
        }));
    }

    public function buildUpdateScheduledForm(string $form_action, Metadata $metadata, Scheduling $scheduling)
    {
        $section = $this->ui_factory->input()->field()->section(
            $this->form_item_builder->editScheduled($metadata, $scheduling)
            + $this->buildEditSchedulingInputs($scheduling), "Update Scheduled")
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
                $metadata = $this->MDParser->parseFormDataEvent($vs);
                $scheduling = $this->schedulingParser->parseUpdateFormData($vs);
                return ['metadata' => $metadata, 'scheduling' => $scheduling];
            }))->withAdditionalTransformation($this->refinery_factory->custom()->constraint(function ($vs) {
                /** @var Scheduling $scheduling */
                $scheduling = $vs['scheduling'];
                return $scheduling->getStart()->getTimestamp() < $scheduling->getEnd()->getTimestamp();
            }, 'Start must be before End'));
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [$section]
        );
    }

    private function buildSchedulingInput(): Input
    {
        $group_no_repeat = $this->ui_factory->input()->field()->group([
            'start_date_time' => $this->ui_factory->input()->field()->dateTime('Start')->withUseTime(true)->withRequired(true),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime('End')->withUseTime(true)->withRequired(true),
        ], 'No');
        $group_repeat = $this->ui_factory->input()->field()->group([
            'start_date' => $this->ui_factory->input()->field()->dateTime('Start Date')->withUseTime(false)->withRequired(true),
            'end_date' => $this->ui_factory->input()->field()->dateTime('End Date')->withUseTime(false)->withRequired(true),
            'weekdays' => $this->ui_factory->input()->field()->multiSelect('Weekdays', [
                'MO' => 'Monday',
                'TU' => 'Tuesday',
                'WE' => 'Wednesday',
                'TH' => 'Thursday',
                'FR' => 'Friday',
                'SA' => 'Saturday',
                'SU' => 'Sunday',
            ])->withRequired(true),
            'start_time' => $this->ui_factory->input()->field()->dateTime('Start Time')->withTimeOnly(true)->withRequired(true),
            'end_time' => $this->ui_factory->input()->field()->dateTime('End Time')->withTimeOnly(true)->withRequired(true),
        ], 'Yes');

        return $this->ui_factory->input()->field()->switchableGroup([
            'no_repeat' => $group_no_repeat,
            'repeat' => $group_repeat
        ], 'Repeat Event')->withRequired(true)->withValue('no_repeat');

    }

    /**
     * @return array
     */
    private function buildEditSchedulingInputs(Scheduling $scheduling): array
    {
        return [
            'start_date_time' => $this->ui_factory->input()->field()->dateTime('Start')->withUseTime(true)
                ->withRequired(true)->withValue($scheduling->getStart()->format('Y-m-d H:i:s')),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime('End')->withUseTime(true)
                ->withRequired(true)->withValue($scheduling->getEnd()->format('Y-m-d H:i:s')),
        ];
    }

}