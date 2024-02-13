<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctPublicationGroupFormGUI
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctPublicationGroupFormGUI extends ilPropertyFormGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'publication_usage' : $module, $fallback);
    }

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

    public function __construct(
        xoctPublicationUsageGUI $parent_gui,
        PublicationUsageGroup $publication_usage_group,
        bool $is_new = true
    ) {
        parent::__construct();
        $this->object = $publication_usage_group;
        $this->parent_gui = $parent_gui;
        $this->parent_gui->setTab();
        $this->ctrl->saveParameter($parent_gui, 'id');
        $this->is_new = $is_new;
        $this->initForm();
    }

    protected function initForm(): void
    {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $te = new ilTextInputGUI($this->getLocaleString('group_' . self::F_NAME,), self::F_NAME);
        $te->setRequired(true);
        $this->addItem($te);

        $max_length = self::F_DISPLAY_NAME_MAX_LENGTH;
        $display_name = (!empty($this->object->getDisplayName()) ? $this->object->getDisplayName(
        ) : '{added display name}');
        $info = sprintf($this->getLocaleString(self::F_DISPLAY_NAME . '_info'), $max_length, strtolower($display_name));
        $te = new ilTextInputGUI($this->getLocaleString(self::F_DISPLAY_NAME), self::F_DISPLAY_NAME);
        $te->setInfo($info);
        $te->setMaxLength($max_length);
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextAreaInputGUI($this->getLocaleString(self::F_DESCRIPTION), self::F_DESCRIPTION);
        $this->addItem($te);
    }

    public function fillForm(): void
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

    protected function initButtons(): void
    {
        if ($this->is_new) {
            $this->setTitle($this->getLocaleString('create_group'));
            $this->addCommandButton(
                xoctPublicationUsageGUI::CMD_CREATE_NEW_GROUP,
                $this->getLocaleString(xoctGUI::CMD_CREATE)
            );
        } else {
            $this->setTitle($this->getLocaleString('edit_group'));
            $this->addCommandButton(
                xoctPublicationUsageGUI::CMD_UPDATE_GROUP,
                $this->getLocaleString(xoctGUI::CMD_UPDATE)
            );
        }

        $this->addCommandButton(xoctGUI::CMD_CANCEL, $this->getLocaleString(xoctGUI::CMD_CANCEL));
    }
}
