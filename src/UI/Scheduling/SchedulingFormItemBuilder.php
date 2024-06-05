<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Scheduling;

use DateTimeZone;
use ilTimeZone;
use ILIAS\Refinery\Custom\Constraint;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\Model\Agent\Agent;
use srag\Plugins\Opencast\Model\Agent\AgentRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingParser;

class SchedulingFormItemBuilder
{
    protected UIFactory $ui_factory;
    private RefineryFactory $refinery_factory;
    private SchedulingParser $schedulingParser;
    private \ilPlugin $plugin;
    private AgentRepository $agentApiRepository;


    public function __construct(
        UIFactory $ui_factory,
        RefineryFactory $refinery_factory,
        SchedulingParser $schedulingParser,
        ilPlugin $plugin,
        AgentRepository $agentApiRepository
    ) {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->schedulingParser = $schedulingParser;
        $this->plugin = $plugin;
        $this->agentApiRepository = $agentApiRepository;
    }

    public function create(): Input
    {
        return $this->ui_factory->input()->field()->section(
            [
                MDFieldDefinition::F_LOCATION => $this->buildSchedulingLocationInput(),
                'scheduling' => $this->buildSchedulingInput()
            ],
            $this->plugin->txt('event_scheduling')
        )->withAdditionalTransformation(
            $this->refinery_factory->custom()->transformation(function (array $vs): array {
                $vs['object'] = $this->schedulingParser->parseCreateFormData($vs);
                return $vs;
            })
        )->withAdditionalTransformation($this->buildConstraintStartAfterNow())
                                ->withAdditionalTransformation($this->buildConstraintStartBeforeEnd());
    }

    public function edit(Scheduling $scheduling, bool $edit_allowed): Input
    {
        return $this->ui_factory->input()->field()->section(
            [
                MDFieldDefinition::F_LOCATION => $this->buildSchedulingLocationInput($scheduling->getAgentId()),
            ] + $this->buildEditSchedulingInputs($scheduling),
            $this->plugin->txt('event_scheduling')
        )->withAdditionalTransformation(
            $this->refinery_factory->custom()->transformation(function (array $vs): array {
                $vs['object'] = $this->schedulingParser->parseUpdateFormData($vs);
                return $vs;
            })
        )->withAdditionalTransformation($this->buildConstraintStartAfterNow())
                                ->withAdditionalTransformation($this->buildConstraintStartBeforeEnd())
                                ->withDisabled(!$edit_allowed);
    }

    private function buildSchedulingInput(): Input
    {
        $group_no_repeat = $this->ui_factory->input()->field()->group([
            'start_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_start'))
                                                  ->withUseTime(true)->withRequired(true),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_end'))
                                                ->withUseTime(true)->withRequired(true),
        ], $this->plugin->txt('no'));
        $group_repeat = $this->ui_factory->input()->field()->group([
            'start_date' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_start'))
                                             ->withUseTime(false)->withRequired(true),
            'end_date' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_end'))
                                           ->withUseTime(false)->withRequired(true),
            'weekdays' => $this->ui_factory->input()->field()->multiSelect(
                $this->plugin->txt('event_multiple_weekdays'),
                [
                    'MO' => $this->plugin->txt('monday'),
                    'TU' => $this->plugin->txt('tuesday'),
                    'WE' => $this->plugin->txt('wednesday'),
                    'TH' => $this->plugin->txt('thursday'),
                    'FR' => $this->plugin->txt('friday'),
                    'SA' => $this->plugin->txt('saturday'),
                    'SU' => $this->plugin->txt('sunday'),
                ]
            )->withRequired(true),
            'start_time' => $this->ui_factory->input()->field()->dateTime(
                $this->plugin->txt('event_multiple_start_time')
            )
                                             ->withTimeOnly(true)->withRequired(true),
            'end_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_multiple_end_time'))
                                           ->withTimeOnly(true)->withRequired(true),
        ], $this->plugin->txt('yes'));

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
                                                  ->withValue(
                                                      $scheduling->getStart()->setTimezone(
                                                          new DateTimeZone(ilTimeZone::_getDefaultTimeZone())
                                                      )->format('Y-m-d H:i:s')
                                                  ),
            'end_date_time' => $this->ui_factory->input()->field()->dateTime($this->plugin->txt('event_end'))
                                                ->withUseTime(true)->withRequired(true)
                                                ->withValue(
                                                    $scheduling->getEnd()->setTimezone(
                                                        new DateTimeZone(ilTimeZone::_getDefaultTimeZone())
                                                    )->format('Y-m-d H:i:s')
                                                ),
        ];
    }

    private function buildSchedulingLocationInput(string $location = ''): Input
    {
        $options = [];
        $agents = $this->agentApiRepository->findAll();
        array_walk($agents, function (Agent $agent) use (&$options): void {
            $options[$agent->getAgentId()] = $agent->getAgentId();
        });
        $input = $this->ui_factory->input()->field()->select(
            $this->plugin->txt('event_location'),
            $options
        )->withRequired(true);
        return $location !== '' && $location !== '0' ? $input->withValue($location) : $input;
    }

    private function buildConstraintStartBeforeEnd(): Constraint
    {
        return $this->refinery_factory->custom()->constraint(function (array $vs): bool {
            /** @var Scheduling $scheduling */
            $scheduling = $vs['object'];
            return $scheduling->getStart()->getTimestamp() < $scheduling->getEnd()->getTimestamp();
        }, $this->plugin->txt('event_msg_end_before_start'));
    }

    private function buildConstraintStartAfterNow(): Constraint
    {
        return $this->refinery_factory->custom()->constraint(function (array $vs): bool {
            /** @var Scheduling $scheduling */
            $scheduling = $vs['object'];
            return $scheduling->getStart()->getTimestamp() > time();
        }, $this->plugin->txt('event_msg_scheduled_in_past'));
    }
}
