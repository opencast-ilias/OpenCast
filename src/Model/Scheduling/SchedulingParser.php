<?php

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeImmutable;
use Exception;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use stdClass;
use xoctException;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

class SchedulingParser
{
    /**
     * @throws Exception
     */
    public function parseCreateFormData(array $form_data) : Scheduling
    {
        $data = $form_data['scheduling'];
        $type = $data[0];
        $scheduling_data = $data[1];
        $channel =  PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL)[0] == "" ? ['default'] :  PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL);
        switch ($type) {
            case 'repeat':
                $start = new DateTimeImmutable($scheduling_data['start_date'] . ' ' . $scheduling_data['start_time']);
                $end = new DateTimeImmutable($scheduling_data['end_date'] . ' ' . $scheduling_data['end_time']);
                $duration = $end->getTimestamp() - $start->getTimestamp();
                return new Scheduling($form_data[MDFieldDefinition::F_LOCATION],
                    $start,
                    $end,
                    $channel,
                    $duration,
                    RRule::fromStartAndWeekdays($start, $scheduling_data['weekdays']));
            case 'no_repeat':
                $start = new DateTimeImmutable($scheduling_data['start_date_time']);
                $end = new DateTimeImmutable($scheduling_data['end_date_time']);
                return new Scheduling($form_data[MDFieldDefinition::F_LOCATION], $start, $end, $channel);
        }
        throw new xoctException(xoctException::INTERNAL_ERROR, $type . ' is not a valid scheduling type');
    }

    public function parseUpdateFormData(array $form_data) : Scheduling
    {
        // for some reason unknown to me, the start/end are already DateTimeImmutables here...
        return new Scheduling($form_data[MDFieldDefinition::F_LOCATION],
            $form_data['start_date_time'],
            $form_data['end_date_time'],
            PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL)[0] == "" ? ['default'] :  PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL)
        );
    }

    public function parseApiResponse(stdClass $data) : Scheduling
    {
        return new Scheduling(
            $data->agent_id,
            new DateTimeImmutable($data->start),
            new DateTimeImmutable($data->end),
            $data->inputs,
            null,
            null

        );
    }

}