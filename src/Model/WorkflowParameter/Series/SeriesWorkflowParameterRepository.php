<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\WorkflowParameter\Series;

use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\WorkflowParameterParser;
use ActiveRecord;

/**
 * Class xoctSeriesWorkflowParameterRepository
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class SeriesWorkflowParameterRepository
{
    protected static $instance;
    protected array $parameters = [];


    public function __construct(private readonly WorkflowParameterParser $workflowParameterParser, private readonly ?\ILIAS\UI\Factory $ui_factory, private readonly ?RefineryFactory $refinery)
    {
    }

    /**
     * @return self
     * @deprecated use constructor
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            global $DIC;
            self::$instance = new self(
                new WorkflowParameterParser(),
                $DIC->ui()->factory(),
                $DIC->refinery()
            );
        }
        return self::$instance;
    }

    public static function getByObjAndParamId(int $obj_id, string $param_id): ?SeriesWorkflowParameter
    {
        return SeriesWorkflowParameter::where(['obj_id' => $obj_id, 'param_id' => $param_id])->first();
    }

    /**
     * @param $param_ids
     */
    public function deleteParamsForAllObjectsById($param_ids): void
    {
        if (!is_array($param_ids)) {
            $param_ids = [$param_ids];
        }
        /** @var SeriesWorkflowParameter $series_parameter */
        foreach (
            SeriesWorkflowParameter::where(['param_id' => $param_ids], ['param_id' => 'IN'])->get() as $series_parameter
        ) {
            $series_parameter->delete();
        }
    }

    /**
     * @param $params WorkflowParameter[]|WorkflowParameter
     */
    public function createParamsForAllObjects($params): void
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        $all_obj_ids = ObjectSettings::getArray(null, 'obj_id');
        foreach ($all_obj_ids as $obj_id) {
            foreach ($params as $param) {
                (new SeriesWorkflowParameter())
                    ->setObjId($obj_id)
                    ->setParamId($param->getId())
                    ->setValueMember($param->getDefaultValueMember())
                    ->setDefaultValueAdmin($param->getDefaultValueAdmin())
                    ->create();
            }
        }
    }

    /**
     * @param $id
     * @param $value_member
     * @param $value_admin
     */
    public function updateById($id, $value_member, $value_admin): void
    {
        SeriesWorkflowParameter::find($id)
                               ->setValueMember($value_member)
                               ->setValueAdmin($value_admin)
                               ->update();
    }

    /**
     * @param $obj_id
     * @param $as_admin
     *
     * @return array<string, array{title: mixed, preset: bool}> Format $id => ['title' => $title, 'preset' => $is_preset]
     */
    public function getParametersInFormForObjId($obj_id, $as_admin): array
    {
        $parameter = [];
        if (PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
            /** @var SeriesWorkflowParameter $input */
            foreach (
                SeriesWorkflowParameter::innerjoin(WorkflowParameter::TABLE_NAME, 'param_id', 'id', ['title'])->where([
                    'obj_id' => $obj_id,
                    ($as_admin ? 'value_admin' : 'value_member') => [
                        SeriesWorkflowParameter::VALUE_SHOW_IN_FORM,
                        SeriesWorkflowParameter::VALUE_SHOW_IN_FORM_PRESET
                    ]
                ])->get() as $input
            ) {
                if ($as_admin) {
                    $preset = ($input->getDefaultValueAdmin() === WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET);
                } else {
                    $preset = ($input->getDefaultValueMember() === WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET);
                }
                $param_id = $input->getParamId();
                $parameter[$param_id] = [
                    'title' => $input->xoct_workflow_param_title ?? $param_id,
                    'preset' => $preset
                ];
            }
        } else {
            /** @var WorkflowParameter $input */
            foreach (
                WorkflowParameter::where([
                    ($as_admin ? 'default_value_admin' : 'default_value_member') => [
                        WorkflowParameter::VALUE_SHOW_IN_FORM,
                        WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET
                    ]
                ])->get() as $input
            ) {
                if ($as_admin) {
                    $preset = ($input->getDefaultValueAdmin() === WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET);
                } else {
                    $preset = ($input->getDefaultValueMember() === WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET);
                }
                $parameter[$input->getId()] = [
                    'title' => $input->getTitle() ?: $input->getId(),
                    'preset' => $preset
                ];
            }
        }
        return $parameter;
    }

    /**
     * @return array Format $id => ['title' => $title, 'preset' => $is_preset]
     */
    public function getGeneralParametersInForm(): array
    {
        $parameter = [];
        /** @var WorkflowParameter $input */
        foreach (
            WorkflowParameter::where([
                'default_value_admin' => [
                    WorkflowParameter::VALUE_SHOW_IN_FORM,
                    WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET
                ]
            ])->get() as $input
        ) {
            $parameter[$input->getId()] = [
                'title' => $input->getTitle() ?: $input->getId(),
                'preset' => ($input->getDefaultValueAdmin() === WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET)
            ];
        }
        return $parameter;
    }

    /**
     * TODO: refactor into a form builder
     */
    public function getFormSectionForObjId(int $obj_id, bool $as_admin, string $workflow_section_title): ?Input
    {
        $items = [];
        foreach ($this->getParametersInFormForObjId($obj_id, $as_admin) as $id => $data) {
            $cb = $this->ui_factory->input()->field()->checkbox($data['title'])->withValue($data['preset']);
            $post_var = 'wp_' . $id;
            $items[$post_var] = $cb;
        }
        if ($items === []) {
            return null;
        }
        return $this->buildFormSection($items, $workflow_section_title);
    }

    /**
     * @param      $obj_id
     *
     * @param bool $as_admin
     *
     * @return int[]
     */
    public function getAutomaticallySetParametersForObjId($obj_id, $as_admin = true): array
    {
        $parameters = [];
        if (PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
            /** @var SeriesWorkflowParameter $xoctSeriesWorkflowParameter */
            foreach (
                SeriesWorkflowParameter::where([
                    'obj_id' => $obj_id,
                    ($as_admin ? 'value_admin' : 'value_member') => SeriesWorkflowParameter::VALUE_ALWAYS_ACTIVE
                ])->get() as $xoctSeriesWorkflowParameter
            ) {
                $parameters[$xoctSeriesWorkflowParameter->getParamId()] = 1;
            }
            /** @var SeriesWorkflowParameter $xoctSeriesWorkflowParameter */
            foreach (
                SeriesWorkflowParameter::where([
                    'obj_id' => $obj_id,
                    ($as_admin ? 'value_admin' : 'value_member') => SeriesWorkflowParameter::VALUE_ALWAYS_INACTIVE
                ])->get() as $xoctSeriesWorkflowParameter
            ) {
                $parameters[$xoctSeriesWorkflowParameter->getParamId()] = 0;
            }
        } else {
            /** @var WorkflowParameter $xoctSeriesWorkflowParameter */
            foreach (
                WorkflowParameter::where(
                    [($as_admin ? 'default_value_admin' : 'default_value_member') => WorkflowParameter::VALUE_ALWAYS_ACTIVE]
                )
                                 ->get() as $xoctSeriesWorkflowParameter
            ) {
                $parameters[$xoctSeriesWorkflowParameter->getId()] = 1;
            }
            /** @var WorkflowParameter $xoctSeriesWorkflowParameter */
            foreach (
                WorkflowParameter::where(
                    [($as_admin ? 'default_value_admin' : 'default_value_member') => WorkflowParameter::VALUE_ALWAYS_INACTIVE]
                )
                                 ->get() as $xoctSeriesWorkflowParameter
            ) {
                $parameters[$xoctSeriesWorkflowParameter->getId()] = 0;
            }
        }
        return $parameters;
    }

    /**
     * @return int[]
     */
    public function getGeneralAutomaticallySetParameters(): array
    {
        $parameters = [];
        /** @var WorkflowParameter $xoctSeriesWorkflowParameter */
        foreach (
            WorkflowParameter::where(['default_value_admin' => WorkflowParameter::VALUE_ALWAYS_ACTIVE])
                             ->get() as $xoctSeriesWorkflowParameter
        ) {
            $parameters[$xoctSeriesWorkflowParameter->getId()] = 1;
        }
        /** @var WorkflowParameter $xoctSeriesWorkflowParameter */
        foreach (
            WorkflowParameter::where(['default_value_admin' => WorkflowParameter::VALUE_ALWAYS_INACTIVE])
                             ->get() as $xoctSeriesWorkflowParameter
        ) {
            $parameters[$xoctSeriesWorkflowParameter->getId()] = 0;
        }
        return $parameters;
    }

    /**
     * @param $obj_id
     */
    public function syncAvailableParameters($obj_id): void
    {
        /** @var WorkflowParameter[] $workflow_parameters */
        $workflow_parameters = WorkflowParameter::get();
        $series_parameters = SeriesWorkflowParameter::where(['obj_id' => $obj_id])->getArray('param_id');

        // create missing
        foreach ($workflow_parameters as $workflow_parameter) {
            if (!isset($series_parameters[$workflow_parameter->getId()])) {
                (new SeriesWorkflowParameter())
                    ->setObjId($obj_id)
                    ->setParamId($workflow_parameter->getId())
                    ->setDefaultValueAdmin($workflow_parameter->getDefaultValueAdmin())
                    ->setValueMember($workflow_parameter->getDefaultValueMember())
                    ->create();
            } else {
                unset($series_parameters[$workflow_parameter->getId()]);
            }
        }

        // delete not existing
        foreach (array_keys($series_parameters) as $id) {
            SeriesWorkflowParameter::find($id)->delete();
        }
    }

    /**
     * TODO: refactor into a form builder
     */
    public function getGeneralFormSection(string $workflow_section_title): ?Input
    {
        $items = [];
        foreach ($this->getGeneralParametersInForm() as $id => $data) {
            $cb = $this->ui_factory->input()->field()->checkbox($data['title'])->withValue($data['preset']);
            $post_var = 'wp_' . $id;
            $items[$post_var] = $cb;
        }
        if ($items === []) {
            return null;
        }
        return $this->buildFormSection($items, $workflow_section_title);
    }

    private function buildFormSection(array $items, string $workflow_section_title): Input
    {
        return $this->ui_factory->input()->field()->section($items, $workflow_section_title)
                                ->withAdditionalTransformation(
                                    $this->refinery->custom()->transformation(function (array $vs): array {
                                        $vs['object'] = $this->workflowParameterParser->configurationFromFormData($vs);
                                        return $vs;
                                    })
                                );
    }
}
