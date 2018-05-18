<?php
/**
 * Class xoctPermissionTemplateFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctVideoPortalSettingsFormGUI extends ilPropertyFormGUI {

    /**
     * @var  xoctConf
     */
    protected $object;
    /**
     * @var xoctConfGUI
     */
    protected $parent_gui;
    /**
     * @var  ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilOpenCastPlugin
     */
    protected $pl;
    /**
     * @var string
     */
    protected $subtab_active;


    /**
     * @param $parent_gui
     */
    public function __construct(xoctPermissionTemplateGUI $parent_gui) {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];
        $this->parent_gui = $parent_gui;
        $this->ctrl = $ilCtrl;
        $this->pl = ilOpenCastPlugin::getInstance();
        $this->lng = $lng;
        $this->initForm();
    }


    /**
     *
     */
    protected function initForm() {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('general'));
        $this->addItem($h);


        // VIDEO PORTAL TITLE
        $te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_VIDEO_PORTAL_TITLE), xoctConf::F_VIDEO_PORTAL_TITLE);
//        $te->setInfo($this->parent_gui->txt(xoctConf::F_VIDEO_PORTAL_TITLE . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        // VIDEO PORTAL LINK
        $te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_VIDEO_PORTAL_LINK), xoctConf::F_VIDEO_PORTAL_LINK);
        $te->setInfo($this->parent_gui->txt(xoctConf::F_VIDEO_PORTAL_LINK . '_info'));
        $te->setRequired(false);
        $this->addItem($te);


    }


    /**
     *
     */
    protected function initButtons() {
        $this->addCommandButton(xoctConfGUI::CMD_UPDATE, $this->parent_gui->txt(xoctConfGUI::CMD_UPDATE));
        $this->addCommandButton(xoctConfGUI::CMD_CANCEL, $this->parent_gui->txt(xoctConfGUI::CMD_CANCEL));
    }


    /**
     *
     */
    public function fillForm() {
        $array = array();
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
    private function getValuesForItem($item, &$array) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = xoctConf::getConfig($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }


    /**
     * @return bool
     */
    public function saveObject() {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }
        xoctConf::set(xoctConf::F_CONFIG_VERSION, xoctConf::CONFIG_VERSION);

        return true;
    }


    /**
     * @param $item
     */
    private function saveValueForItem($item) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            xoctConf::set($key, $this->getInput($key));
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkForSubItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI AND !$item instanceof ilMultiSelectInputGUI;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI;
    }



}