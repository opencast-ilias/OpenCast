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
    private ObjectiveCollection $precondition;

    public function __construct(ObjectiveCollection $precondition, ilDatabaseUpdateSteps $steps)
    {
        $this->precondition = $precondition;
        parent::__construct($steps);
    }

    public function getPreconditions(Environment $environment): array
    {
        return array_merge([$this->precondition], parent::getPreconditions($environment));
    }

}
