<?php

use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\DI\OpencastDIC;

/**
 * @ilCtrl_IsCalledBy xoctMetadataConfigRouterGUI : xoctMainGUI
 */
class xoctMetadataConfigRouterGUI
{
    use DICTrait;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const SUBTAB_EVENTS = 'events';
    public const SUBTAB_SERIES = 'series';


    public function executeCommand()
    {
        global $DIC;
        $nextClass = self::dic()->ctrl()->getNextClass();

        $opencast_dic = OpencastDIC::getInstance();
        switch ($nextClass) {
            case strtolower(xoctSeriesMetadataConfigGUI::class):
                $this->setSubTabs(self::SUBTAB_SERIES);
                $gui = new xoctSeriesMetadataConfigGUI(
                    $opencast_dic->metadata()->confRepositorySeries(),
                    $opencast_dic->metadata()->catalogueFactory(),
                    $DIC,
                    ilOpenCastPlugin::getInstance()
                );
                self::dic()->ctrl()->forwardCommand($gui);
                break;
            case strtolower(xoctEventMetadataConfigGUI::class):
            default:
                $this->setSubTabs(self::SUBTAB_EVENTS);
                $gui = new xoctEventMetadataConfigGUI(
                    $opencast_dic->metadata()->confRepositoryEvent(),
                    $opencast_dic->metadata()->catalogueFactory(),
                    $DIC,
                    ilOpenCastPlugin::getInstance()
                );
                self::dic()->ctrl()->forwardCommand($gui);
                break;
        }
    }

    private function setSubTabs(string $active_subtab)
    {
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_EVENTS,
            self::plugin()->translate('subtab_' . self::SUBTAB_EVENTS),
            self::dic()->ctrl()->getLinkTargetByClass(xoctEventMetadataConfigGUI::class)
        );
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_SERIES,
            self::plugin()->translate('subtab_' . self::SUBTAB_SERIES),
            self::dic()->ctrl()->getLinkTargetByClass(xoctSeriesMetadataConfigGUI::class)
        );
        self::dic()->tabs()->activateSubTab($active_subtab);
    }
}
