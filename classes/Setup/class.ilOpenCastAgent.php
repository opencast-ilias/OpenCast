<?php

declare(strict_types=1);

use ILIAS\Setup;

/**
 * Class ilOpenCastAgent
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ilOpenCastAgent extends Setup\Agent\NullAgent
{
    /**
     * @inheritDoc
     */
    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new \ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilOpenCastDBUpdateSteps());
    }

    /**
     * @inheritDoc
     */
    public function getUpdateObjective(Setup\Config $config = null): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            "Updates of OpenCast object",
            false,
            ...$this->getObjectives()
        );
    }

    /**
     * Helper function to return all additional update objectives
     *
     * @return array
     */
    protected function getObjectives(): array
    {
        $objectives = [];

        // NOTE: Because there are already 2 custom rbac operations, we don't need to add  the common rbac operations on xoct type!
        // Add custom rbac operations
        $objectives[] = new ilAccessCustomRBACOperationAddedObjective(
            "rep_robj_xoct_perm_download",
            "Download",
            "object",
            2030,
            [ilOpenCastPlugin::PLUGIN_ID]
        );
        $objectives[] = new ilAccessCustomRBACOperationAddedObjective(
            "rep_robj_xoct_perm_record",
            "Record",
            "object",
            2040,
            [ilOpenCastPlugin::PLUGIN_ID]
        );
        $objectives[] = new ilAccessCustomRBACOperationAddedObjective(
            "rep_robj_xoct_perm_schedule",
            "Schedule",
            "object",
            2050,
            [ilOpenCastPlugin::PLUGIN_ID]
        );

        // db update steps
        $objectives[] = new ilDatabaseUpdateStepsExecutedObjective(new ilOpenCastDBUpdateSteps());

        return $objectives;
    }

    /**
     * Run with `php setup/setup.php achieve OpenCast.OpencastRBACRights -vv`
     */
    public function getNamedObjectives(?Setup\Config $config = null): array
    {
        return [
            "OpencastRBACRights" => new Setup\ObjectiveConstructor(
                "Updating Opencast RBAC permissions list",
                function () {
                    return new ilOpenCastUpdateRBACPermsListObjective();
                }
            )
        ];
    }
}
