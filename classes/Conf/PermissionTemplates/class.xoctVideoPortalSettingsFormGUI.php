<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctPermissionTemplateFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctVideoPortalSettingsFormGUI extends ilPropertyFormGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'config' : $module, $fallback);
    }

    /**
     * @var  PluginConfig
     */
    protected $object;
    /**
     * @var xoctConfGUI
     */
    protected \xoctPermissionTemplateGUI $parent_gui;
    /**
     * @var string
     */
    protected $subtab_active;

    /**
     * @param $parent_gui
     */
    public function __construct(xoctPermissionTemplateGUI $parent_gui)
    {
        parent::__construct();
        $this->parent_gui = $parent_gui;
        $this->initForm();
    }

    /**
     *
     */
    protected function initForm(): void
    {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('general'));
        $this->addItem($h);

        // VIDEO PORTAL TITLE
        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_VIDEO_PORTAL_TITLE),
            PluginConfig::F_VIDEO_PORTAL_TITLE
        );
        //        $te->setInfo($this->getLocaleString(xoctConf::F_VIDEO_PORTAL_TITLE . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        // VIDEO PORTAL LINK
        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_VIDEO_PORTAL_LINK),
            PluginConfig::F_VIDEO_PORTAL_LINK
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_VIDEO_PORTAL_LINK . '_info'));
        $te->setRequired(false);
        $this->addItem($te);
    }

    protected function initButtons(): void
    {
        $this->addCommandButton(xoctGUI::CMD_UPDATE, $this->getLocaleString(xoctGUI::CMD_UPDATE));
    }


    public function fillForm(): void
    {
        $array = [];
        foreach ($this->getItems() as $item) {
            $this->getValuesForItem($item, $array);
        }
        $this->setValuesByArray($array);
    }

    /**
     * @param $item
     * @param $array
     *
     * @internal param $key
     */
    private function getValuesForItem($item, &$array): void
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = PluginConfig::getConfig($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }

    public function saveObject(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }
        PluginConfig::set(PluginConfig::F_CONFIG_VERSION, PluginConfig::CONFIG_VERSION);

        return true;
    }

    /**
     * @param $item
     */
    private function saveValueForItem($item): void
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            PluginConfig::set($key, $this->getInput($key));
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }

    /**
     * @param $item
     */
    public static function checkForSubItem($item): bool
    {
        return !$item instanceof ilFormSectionHeaderGUI && !$item instanceof ilMultiSelectInputGUI;
    }

    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkItem($item)
    {
        return !$item instanceof ilFormSectionHeaderGUI;
    }
}
