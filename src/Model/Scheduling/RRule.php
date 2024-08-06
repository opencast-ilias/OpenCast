<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeImmutable;

class RRule
{
    public function __construct(private readonly string $value)
    {
    }

    public static function fromStartAndWeekdays(DateTimeImmutable $start, array $weekdays): self
    {
        $byhour = $start->format('H');
        $byminute = $start->format('i');
        $byday = implode(',', $weekdays);
        $rrule = "FREQ=WEEKLY;BYDAY=$byday;BYHOUR=$byhour;BYMINUTE=$byminute;";
        return new self($rrule);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
