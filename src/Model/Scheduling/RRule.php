<?php

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeImmutable;

class RRule
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromStartAndWeekdays(DateTimeImmutable $start, array $weekdays) : self
    {
        $byhour = $start->format('H');
        $byminute = $start->format('i');
        $byday = implode(',', $weekdays);
        $rrule = "FREQ=WEEKLY;BYDAY=$byday;BYHOUR=$byhour;BYMINUTE=$byminute;";
        return new self($rrule);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}