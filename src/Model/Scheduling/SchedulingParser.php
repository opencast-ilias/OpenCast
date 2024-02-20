<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeZone;
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
    public function parseCreateFormData(array $form_data): Scheduling
    {
        $data = $form_data['scheduling'];
        $type = $data[0];
        $scheduling_data = $data[1];
        $channel = PluginConfig::getConfig(
            PluginConfig::F_SCHEDULE_CHANNEL
        )[0] == "" ? ['default'] : PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL);
        switch ($type) {
            case 'repeat':
                $start_date = $scheduling_data['start_date'] instanceof DateTimeImmutable ? $scheduling_data['start_date']->format('Y-m-d') : $scheduling_data['start_date'];
                $start_time = $scheduling_data['start_time'] instanceof DateTimeImmutable ? $scheduling_data['start_time']->format('H:i:s') : $scheduling_data['start_time'];
                $start = new DateTimeImmutable($start_date . ' ' . $start_time);
                $end_date = $scheduling_data['end_date'] instanceof DateTimeImmutable ? $scheduling_data['end_date']->format('Y-m-d') : $scheduling_data['end_date'];
                $end_time = $scheduling_data['end_time'] instanceof DateTimeImmutable ? $scheduling_data['end_time']->format('H:i:s') : $scheduling_data['end_time'];
                $end = new DateTimeImmutable($end_date . ' ' . $end_time);

                $duration = $end->getTimestamp() - $start->getTimestamp();
                return new Scheduling(
                    $form_data[MDFieldDefinition::F_LOCATION],
                    $start->setTimezone(new DateTimeZone('GMT')),
                    $end->setTimezone(new DateTimeZone('GMT')),
                    $channel,
                    $duration,
                    RRule::fromStartAndWeekdays($start, $scheduling_data['weekdays'])
                );
            case 'no_repeat':
                if (isset($scheduling_data['start_date_time']) && $scheduling_data['start_date_time'] instanceof DateTimeImmutable) {
                    $start = $scheduling_data['start_date_time'];
                } else {
                    $start = new DateTimeImmutable($scheduling_data['start_date_time']);
                }

                if (isset($scheduling_data['end_date_time']) && $scheduling_data['end_date_time'] instanceof DateTimeImmutable) {
                    $end = $scheduling_data['end_date_time'];
                } else {
                    $end = new DateTimeImmutable($scheduling_data['end_date_time']);
                }

                return new Scheduling(
                    $form_data[MDFieldDefinition::F_LOCATION],
                    $start->setTimezone(new DateTimeZone('GMT')),
                    $end->setTimezone(new DateTimeZone('GMT')),
                    $channel
                );
        }
        throw new xoctException(xoctException::INTERNAL_ERROR, $type . ' is not a valid scheduling type');
    }

    public function parseUpdateFormData(array $form_data): Scheduling
    {
        // for some reason unknown to me, the start/end are already DateTimeImmutables here...
        return new Scheduling(
            $form_data[MDFieldDefinition::F_LOCATION],
            $form_data['start_date_time']->setTimezone(new DateTimeZone('GMT')),
            $form_data['end_date_time']->setTimezone(new DateTimeZone('GMT')),
            PluginConfig::getConfig(PluginConfig::F_SCHEDULE_CHANNEL)[0] == "" ? ['default'] : PluginConfig::getConfig(
                PluginConfig::F_SCHEDULE_CHANNEL
            )
        );
    }

    public function parseApiResponse(stdClass $data): Scheduling
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
