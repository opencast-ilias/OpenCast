<?php

use ILIAS\Setup\Environment;
use ILIAS\Setup\ObjectiveCollection;

/**
 * Class ilOpenCastUpdateStepsExecutedObjective
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilOpenCastUpdateStepsExecutedObjective extends ilDatabaseUpdateStepsExecutedObjective
{
    public function __construct(private readonly ObjectiveCollection $precondition, ilDatabaseUpdateSteps $steps)
    {
        parent::__construct($steps);
    }

    public function getPreconditions(Environment $environment): array
    {
        return array_merge([$this->precondition], parent::getPreconditions($environment));
    }

}
