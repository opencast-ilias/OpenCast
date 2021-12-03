<?php

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeImmutable;
use Exception;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use stdClass;
use xoctException;

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
        switch ($type) {
            case 'repeat':
                $start = new DateTimeImmutable($scheduling_data['start_date'] . ' ' . $scheduling_data['start_time']);
                $end = new DateTimeImmutable($scheduling_data['end_date'] . ' ' . $scheduling_data['end_time']);
                $duration = $end->getTimestamp() - $start->getTimestamp();
                return new Scheduling($form_data['md_' . MDFieldDefinition::F_LOCATION],
                    $start,
                    $end,
                    $duration,
                    RRule::fromStartAndWeekdays($start, $scheduling_data['weekdays']));
            case 'no_repeat':
                $start = new DateTimeImmutable($scheduling_data['start_date_time']);
                $end = new DateTimeImmutable($scheduling_data['end_date_time']);
                return new Scheduling($form_data['md_' . MDFieldDefinition::F_LOCATION], $start, $end);
        }
        throw new xoctException(xoctException::INTERNAL_ERROR, $type . ' is not a valid scheduling type');
    }

    public function parseUpdateFormData(array $form_data) : Scheduling
    {
        // for some reason unknown to me, the start/end are already DateTimeImmutables here...
        return new Scheduling($form_data['md_' . MDFieldDefinition::F_LOCATION],
            $form_data['start_date_time'],
            $form_data['end_date_time']);
    }

    public function parseApiResponse(stdClass $data) : Scheduling
    {
        return new Scheduling(
            $data->agent_id,
            new DateTimeImmutable($data->start),
            new DateTimeImmutable($data->end),
            null,
            null,
            $data->inputs
        );
    }

}