<?php

declare(strict_types=1);

/**
 * Class ilOpenCastDBUpdateSteps
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ilOpenCastDBUpdateSteps implements \ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
    }

    // public function step_1(): void
    // {
    //     // DB Update codes for step 1
    // }
}
