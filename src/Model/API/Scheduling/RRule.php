<?php

namespace srag\Plugins\Opencast\Model\API\Scheduling;

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

    public static function fromStartAndWeekdays(\DateTimeImmutable $start, array $weekdays) : self
    {
        $start_ts = $start->getTimestamp();
        $byhour = floor($start_ts / 3600);
        $byminute = floor($start_ts / 60) % 60;

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