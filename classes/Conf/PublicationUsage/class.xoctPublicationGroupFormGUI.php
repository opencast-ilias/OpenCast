<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;

/**
 * Class xoctPublicationGroupFormGUI
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctPublicationGroupFormGUI extends ilPropertyFormGUI
{
    public const F_NAME = 'name';
    public const F_DISPLAY_NAME = 'display_name';
    public const F_DESCRIPTION = 'description';
    public const F_DISPLAY_NAME_MAX_LENGTH = 10;

    /**
     * @var PublicationUsageGroup
     */
    protected $object;
    /**
     * @var xoctPublicationUsageGUI
     */
    protected $parent_gui;
    /**
     * @var bool $is_new
     */
    protected $is_new = true;



    /**
     * @param xoctPublicationUsageGUI $parent_gui
     * @param PublicationUsageGroup   $xoctPublicationUsageGroup
     * @param bool $is_new
     */
    public function __construct($parent_gui, $xoctPublicationUsageGroup, $is_new = true)
    {
        parent::__construct();
        $this->object = $xoctPublicationUsageGroup;
        $this->parent_gui = $parent_gui;
        $this->parent_gui->setTab();
        $this->ctrl->saveParameter($parent_gui, 'id');
        $this->is_new = $is_new;
        $this->initForm();
    }


    /**
     *
     */
    protected function initForm()
    {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $te = new ilTextInputGUI($this->txt(self::F_NAME), self::F_NAME);
        $te->setRequired(true);
        $this->addItem($te);

        $max_lenght = self::F_DISPLAY_NAME_MAX_LENGTH;
        $display_name = (!empty($this->object->getDisplayName()) ? $this->object->getDisplayName() : '{added display name}');
        $info = sprintf($this->txt(self::F_DISPLAY_NAME . '_info'), $max_lenght, strtolower($display_name));
        $te = new ilTextInputGUI($this->txt(self::F_DISPLAY_NAME), self::F_DISPLAY_NAME);
        $te->setInfo($info);
        $te->setMaxLength($max_lenght);
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextAreaInputGUI($this->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
        $this->addItem($te);
    }


    /**
     * @param $lang_var
     *
     * @return string
     */
    protected function txt($lang_var): string
    {
        return $this->parent_gui->txt("group_{$lang_var}");
    }


    /**
     *
     */
    public function fillForm()
    {
        $array = [
            self::F_NAME => $this->object->getName(),
            self::F_DISPLAY_NAME => $this->object->getDisplayName(),
            self::F_DESCRIPTION => $this->object->getDescription(),
        ];

        $this->setValuesByArray($array);
    }


    /**
     * returns whether checkinput was successful or not.
     *
     * @return bool
     */
    public function fillObject(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        $this->object->setName($this->getInput(self::F_NAME));
        $this->object->setDisplayName($this->getInput(self::F_DISPLAY_NAME));
        $this->object->setDescription($this->getInput(self::F_DESCRIPTION));

        return true;
    }


    /**
     * @return bool
     */
    public function saveObject(): bool
    {
        if (!$this->fillObject()) {
            return false;
        }
        if ($this->is_new) {
            $this->object->create();
        } else {
            $this->object->update();
        }

        return true;
    }


    /**
     *
     */
    protected function initButtons()
    {
        if ($this->is_new) {
            $this->setTitle($this->parent_gui->txt('create_group'));
            $this->addCommandButton(xoctPublicationUsageGUI::CMD_CREATE_NEW_GROUP, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CREATE));
        } else {
            $this->setTitle($this->parent_gui->txt('edit_group'));
            $this->addCommandButton(xoctPublicationUsageGUI::CMD_UPDATE_GROUP, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_UPDATE));
        }

        $this->addCommandButton(xoctPublicationUsageGUI::CMD_CANCEL, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CANCEL));
    }
}
