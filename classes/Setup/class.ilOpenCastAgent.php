<?php

declare(strict_types=1);
use ILIAS\Setup\Metrics\Storage;
use ILIAS\Setup\ObjectiveConstructor;

use ILIAS\Setup;
use ILIAS\Setup\Config;
use ILIAS\Setup\Objective;
use ILIAS\Setup\ObjectiveCollection;
use ILIAS\Refinery\Factory;

/**
 * Class ilOpenCastAgent
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilOpenCastAgent extends ilPluginDefaultAgent
{
    public function __construct(
        private readonly Factory $refinery,
        private readonly \ILIAS\Data\Factory $data_factory,
        private readonly \ilLanguage $lng
    ) {
        parent::__construct('OpenCast');
    }


    public function getStatusObjective(Storage $storage): Objective
    {
        return new \ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilOpenCastDBUpdateSteps());
    }

    public function getInstallObjective(Config $config = null): Objective
    {
        $general_update = parent::getInstallObjective($config);

        return new ObjectiveCollection(
            'Opencast-Plugin Installation',
            true,
            $general_update,
            ...$this->getObjectives($general_update)
        );

    }

    public function getUpdateObjective(Config $config = null): Objective
    {
        $general_update = parent::getUpdateObjective($config);

        return new ObjectiveCollection(
            'Opencast-Plugin Update',
            true,
            $general_update,
            ...$this->getObjectives($general_update)
        );
    }

    /**
     * Helper function to return all additional update objectives
     *
     * @return Objective[]
     */
    protected function getObjectives(ObjectiveCollection $precondition): array
    {
        return [
            // NOTE: Because there are already 2 custom rbac operations, we don't need to add  the common rbac operations on xoct type!
            // Add custom rbac operations
            new ilAccessCustomRBACOperationAddedObjective(
                "rep_robj_xoct_perm_download",
                "Download",
                "object",
                2030,
                [ilOpenCastPlugin::PLUGIN_ID]
            ),
            new ilAccessCustomRBACOperationAddedObjective(
                "rep_robj_xoct_perm_record",
                "Record",
                "object",
                2040,
                [ilOpenCastPlugin::PLUGIN_ID]
            ),
            new ilAccessCustomRBACOperationAddedObjective(
                "rep_robj_xoct_perm_schedule",
                "Schedule",
                "object",
                2050,
                [ilOpenCastPlugin::PLUGIN_ID]
            ),
            // db update steps
            new ilOpenCastUpdateStepsExecutedObjective($precondition, new ilOpenCastDBUpdateSteps()),
        ];
    }

    /**
     * Run with `php setup/setup.php achieve OpenCast.OpencastRBACRights -vv`
     */
    public function getNamedObjectives(?Config $config = null): array
    {
        return [
            "OpencastRBACRights" => new ObjectiveConstructor(
                "Updating Opencast RBAC permissions list",
                fn(): \ilOpenCastUpdateRBACPermsListObjective => new ilOpenCastUpdateRBACPermsListObjective()
            )
        ];
    }
}
