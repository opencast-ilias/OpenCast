<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * ListGUI implementation for OpenCast object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @author        Gabriel Comte <gc@studer-raimann.ch>
 *
 *
 * @version       1.0.00
 */
class ilObjOpenCastListGUI extends ilObjectPluginListGUI
{
    /**
     * @var bool
     */
    public $payment_enabled;
    public function initType(): void
    {
        $this->setType(ilOpenCastPlugin::PLUGIN_ID);
    }

    public function getGuiClass(): string
    {
        return 'ilObjOpenCastGUI';
    }

    public function initCommands(): array
    {
        // Always set
        $this->timings_enabled = true;
        $this->subscribe_enabled = true;
        $this->payment_enabled = false;
        $this->link_enabled = true;
        $this->info_screen_enabled = true;
        $this->delete_enabled = true;
        $this->notes_enabled = true;
        $this->comments_enabled = true;

        // Should be overwritten according to status
        $this->cut_enabled = true;

        $this->copy_enabled = ilOpenCastPlugin::ALLOW_DUPLICATION;

        return [
            [
                'permission' => 'read',
                'cmd' => ilObjOpenCastGUI::CMD_SHOW_CONTENT,
                'default' => true,
            ],
            [
                'permission' => 'write',
                'cmd' => ilObjOpenCastGUI::CMD_REDIRECT_SETTING,
                'lang_var' => 'edit'
            ]
        ];
    }

    public function insertDeleteCommand(): void
    {
        if ($this->std_cmd_only) {
            return;
        }

        if ($this->getContainerObject() instanceof ilAdministrationCommandHandling) {
            if ($this->checkCommandAccess('delete', '', $this->ref_id, $this->type)) {
                $this->ctrl->setParameterByClass("ilObjOpenCastGUI", 'item_ref_id', $this->getCommandId());
                $cmd_link = $this->ctrl->getLinkTargetByClass("ilObjOpenCastGUI", "delete");
                $this->insertCommand($cmd_link, $this->lng->txt("delete"));
                $this->adm_commands_included = true;
                return;
            }
            return;
        }

        if ($this->checkCommandAccess('delete', '', $this->ref_id, $this->type)) {
            $this->ctrl->setParameterByClass(
                "ilObjOpenCastGUI",
                "ref_id",
                $this->parent_ref_id
            );
            $this->ctrl->setParameterByClass("ilObjOpenCastGUI", "item_ref_id", $this->getCommandId());
            $cmd_link = $this->ctrl->getLinkTargetByClass("ilObjOpenCastGUI", "deleteObject");
            $this->insertCommand(
                $cmd_link,
                $this->lng->txt("delete"),
                "",
                ""
            );
            $this->adm_commands_included = true;
        }
    }

    protected function getObject(): \ilObjOpenCast
    {
        return new ilObjOpenCast($this->ref_id);
    }

    /**
     * @throws xoctException
     */
    protected function getOpenCast(bool $get_exceoptions = false): ObjectSettings
    {
        $objectSettings = new ObjectSettings();
        if (!isset($this->obj_id)) {
            return $objectSettings;
        }
        try {
            PluginConfig::setApiSettings();
            $objectSettings = ObjectSettings::findOrGetInstance($this->obj_id);
        } catch (xoctException $e) {
            if ($get_exceoptions) {
                throw $e;
            }
        }

        return $objectSettings;
    }

    /**
     * Get item properties
     *
     * @return    array        array of property arrays:
     *                        'alert' (boolean) => display as an alert property (usually in red)
     *                        'property' (string) => property name
     *                        'value' (string) => property value
     */
    #[ReturnTypeWillChange]
    public function getCustomProperties(/*array*/ $prop): array
    {
        $props = parent::getCustomProperties([]);
        try {
            $objectSettings = $this->getOpenCast(true);
            if (!$objectSettings instanceof ObjectSettings) {
                return $props;
            }

            if (!$objectSettings->isOnline()) {
                $props[] = [
                    'alert' => true,
                    'newline' => true,
                    'property' => 'Status',
                    'value' => 'Offline',
                    'propertyNameVisible' => true
                ];
            }
        } catch (xoctException $e) {
            $props[] = [
                'alert' => true,
                'newline' => true,
                'property' => 'API',
                'value' => $e->getMessage(),
                'propertyNameVisible' => false
            ];
        }

        return $props;
    }

    /**
     * get all alert properties
     */
    public function getAlertProperties(): array
    {
        $alert = [];
        foreach ($this->getCustomProperties([]) as $prop) {
            if ($prop['alert']) {
                $alert[] = $prop;
            }
        }

        return $alert;
    }

    /**
     * @param $unix_timestamp
     *
     * @return string formatted date
     */

    public static function format_date_time($unix_timestamp): string
    {
        global $DIC;
        $language = $DIC->language();
        $now = time();
        $today = $now - $now % (60 * 60 * 24);
        $yesterday = $today - 60 * 60 * 24;

        if ($unix_timestamp < $yesterday) {
            // given date is older than two days
            $date = date('d. M Y', $unix_timestamp);
        } elseif ($unix_timestamp < $today) {
            // given date yesterday
            $date = $language->txt('yesterday');
        } else {
            // given date is today
            $date = $language->txt('today');
        }

        return $date . ', ' . date('H:i', $unix_timestamp);
    }
}
