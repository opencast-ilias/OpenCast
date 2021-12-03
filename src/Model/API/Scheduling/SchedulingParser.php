<?php

namespace srag\Plugins\Opencast\Model\API\Scheduling;

use DateTimeImmutable;
use Exception;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctException;

class SchedulingParser
{
    /**
     * @throws Exception
     */
    public function parseFormData(array $form_data) : Scheduling
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

}