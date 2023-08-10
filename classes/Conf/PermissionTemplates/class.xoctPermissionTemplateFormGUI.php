<?php

use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;

/**
 * Class xoctPermissionTemplateFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateFormGUI extends ilPropertyFormGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const F_DEFAULT = 'is_default';
    public const F_TITLE_DE = 'title_de';
    public const F_TITLE_EN = 'title_en';
    public const F_INFO_DE = 'info_de';
    public const F_INFO_EN = 'info_en';
    public const F_ROLE = 'role';
    public const F_READ = 'read';
    public const F_WRITE = 'write';
    public const F_ADDITIONAL_ACL_ACTIONS = 'additional_acl_actions';
    public const F_ADDITIONAL_ACTIONS_DOWNLOAD = 'additional_actions_download';
    public const F_ADDITIONAL_ACTIONS_ANNOTATE = 'additional_actions_annotate';
    public const F_ADDITIONAL_ROLE_ACTIONS = 'additional_role_actions';
    public const F_ADDED_ROLE = 'added_role';
    public const F_ADDED_ROLE_NAME = 'added_role_name';
    public const F_ADDED_ROLE_READ = 'added_role_read';
    public const F_ADDED_ROLE_WRITE = 'added_role_write';
    public const F_ADDED_ROLE_ACL_ACTIONS = 'added_role_acl_actions';
    public const F_ADDED_ROLE_ACTIONS_DOWNLOAD = 'added_role_actions_download';
    public const F_ADDED_ROLE_ACTIONS_ANNOTATE = 'added_role_actions_annotate';

    /**
     * @var  PermissionTemplate
     */
    protected $object;
    /**
     * @var xoctPermissionTemplateGUI
     */
    protected $parent_gui;
    /**
     * @var bool
     */
    protected $is_new;

    /**
     * @param xoctPermissionTemplateGUI $parent_gui
     * @param PermissionTemplate        $xoctPermissionTemplate
     */
    public function __construct($parent_gui, PermissionTemplate $xoctPermissionTemplate)
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        parent::__construct();
        $this->object = $xoctPermissionTemplate;
        $this->parent_gui = $parent_gui;
        $ctrl->saveParameter($parent_gui, xoctPermissionTemplateGUI::IDENTIFIER);
        $this->is_new = ($this->object->getId() == 0);
        $this->initForm();
    }

    /**
     *
     */
    protected function initForm()
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $input = new ilCheckboxInputGUI($this->txt(self::F_DEFAULT), self::F_DEFAULT);
        $input->setInfo($this->txt(self::F_DEFAULT . '_info'));
        $this->addItem($input);

        $input = new ilTextInputGUI($this->txt(self::F_TITLE_DE), self::F_TITLE_DE);
        $input->setInfo($this->txt(self::F_TITLE_DE . '_info'));
        $input->setRequired(true);
        $this->addItem($input);

        $input = new ilTextInputGUI($this->txt(self::F_TITLE_EN), self::F_TITLE_EN);
        $input->setInfo($this->txt(self::F_TITLE_EN . '_info'));
        $input->setRequired(true);
        $this->addItem($input);

        $input = new ilTextAreaInputGUI($this->txt(self::F_INFO_DE), self::F_INFO_DE);
        $input->setInfo($this->txt(self::F_INFO_DE . '_info'));
        $input->setRequired(false);
        $this->addItem($input);

        $input = new ilTextAreaInputGUI($this->txt(self::F_INFO_EN), self::F_INFO_EN);
        $input->setInfo($this->txt(self::F_INFO_EN . '_info'));
        $input->setRequired(false);
        $this->addItem($input);

        $input = new ilTextInputGUI($this->txt(self::F_ROLE), self::F_ROLE);
        $input->setInfo($this->txt(self::F_ROLE . '_info'));
        $input->setRequired(true);
        $this->addItem($input);

        $input = new ilCheckboxInputGUI($this->txt(self::F_READ), self::F_READ);
        $input->setInfo($this->txt(self::F_READ . '_info'));
        $this->addItem($input);

        $input = new ilCheckboxInputGUI($this->txt(self::F_WRITE), self::F_WRITE);
        $input->setInfo($this->txt(self::F_WRITE . '_info'));
        $this->addItem($input);

        $input = new ilTextInputGUI($this->txt(self::F_ADDITIONAL_ACL_ACTIONS), self::F_ADDITIONAL_ACL_ACTIONS);
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACL_ACTIONS . '_info'));
        $this->addItem($input);

        $input = new ilTextInputGUI(
            $this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD),
            self::F_ADDITIONAL_ACTIONS_DOWNLOAD
        );
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD . '_info'));
        $this->addItem($input);

        $input = new ilTextInputGUI(
            $this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE),
            self::F_ADDITIONAL_ACTIONS_ANNOTATE
        );
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE . '_info'));
        $this->addItem($input);

        $input = new ilCheckboxInputGUI($this->txt(self::F_ADDITIONAL_ROLE_ACTIONS), self::F_ADDED_ROLE);
        $input->setInfo($this->txt(self::F_ADDITIONAL_ROLE_ACTIONS . '_info'));

        if ($input->getValue()) {
            $newRole = $this->addAdditionalRolePermission();
            foreach ($newRole as $field) {
                $input->addSubItem($field);
            }
        }
        $this->addItem($input);
    }

    /**
     *
     */
    protected function initButtons()
    {
        $this->ctrl->setParameter(
            $this->parent_gui,
            'subtab_active',
            xoctPermissionTemplateGUI::SUBTAB_PERMISSION_TEMPLATES
        );
        if ($this->is_new) {
            $this->setTitle($this->lng->txt('create'));
            $this->addCommandButton(
                xoctPermissionTemplateGUI::CMD_CREATE,
                $this->lng->txt(xoctPermissionTemplateGUI::CMD_CREATE)
            );
        } else {
            $this->setTitle($this->lng->txt('edit'));
            $this->addCommandButton(
                xoctPermissionTemplateGUI::CMD_UPDATE_TEMPLATE,
                $this->lng->txt(xoctPermissionTemplateGUI::CMD_UPDATE)
            );
        }

        $this->addCommandButton(
            xoctPermissionTemplateGUI::CMD_CANCEL,
            $this->lng->txt(xoctPermissionTemplateGUI::CMD_CANCEL)
        );
    }

    protected function addAdditionalRolePermission(): array
    {
        $array = [];
        $input = new ilTextInputGUI($this->txt(self::F_ROLE), self::F_ADDED_ROLE_NAME);
        $input->setInfo($this->txt(self::F_ROLE . '_info'));
        $input->setRequired(true);
        $array[] = $input;

        $input = new ilCheckboxInputGUI($this->txt(self::F_READ), self::F_ADDED_ROLE_READ);
        $input->setInfo($this->txt(self::F_READ . '_info'));
        $array[] = $input;

        $input = new ilCheckboxInputGUI($this->txt(self::F_WRITE), self::F_ADDED_ROLE_WRITE);
        $input->setInfo($this->txt(self::F_WRITE . '_info'));
        $array[] = $input;

        $input = new ilTextInputGUI($this->txt(self::F_ADDITIONAL_ACL_ACTIONS), self::F_ADDED_ROLE_ACL_ACTIONS);
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACL_ACTIONS . '_info'));
        $array[] = $input;

        $input = new ilTextInputGUI(
            $this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD),
            self::F_ADDED_ROLE_ACTIONS_DOWNLOAD
        );
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD . '_info'));
        $array[] = $input;

        $input = new ilTextInputGUI(
            $this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE),
            self::F_ADDED_ROLE_ACTIONS_ANNOTATE
        );
        $input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE . '_info'));
        $array[] = $input;

        return $array;
    }

    public function fillForm()
    {
        $array = [
            self::F_DEFAULT => $this->object->isDefault(),
            self::F_TITLE_DE => $this->object->getTitleDE(),
            self::F_TITLE_EN => $this->object->getTitleEN(),
            self::F_INFO_DE => $this->object->getInfoDE(),
            self::F_INFO_EN => $this->object->getInfoEN(),
            self::F_ROLE => $this->object->getRole(),
            self::F_READ => $this->object->getRead(),
            self::F_WRITE => $this->object->getWrite(),
            self::F_ADDITIONAL_ACL_ACTIONS => $this->object->getAdditionalAclActions(),
            self::F_ADDITIONAL_ACTIONS_DOWNLOAD => $this->object->getAdditionalActionsDownload(),
            self::F_ADDITIONAL_ACTIONS_ANNOTATE => $this->object->getAdditionalActionsAnnotate(),
            self::F_ADDED_ROLE => $this->object->getAddedRole(),
            self::F_ADDED_ROLE_NAME => $this->object->getAddedRoleName(),
            self::F_ADDED_ROLE_READ => $this->object->getAddedRoleRead(),
            self::F_ADDED_ROLE_WRITE => $this->object->getAddedRoleWrite(),
            self::F_ADDED_ROLE_ACL_ACTIONS => $this->object->getAddedRoleAclActions(),
            self::F_ADDED_ROLE_ACTIONS_DOWNLOAD => $this->object->getAddedRoleActionsDownload(),
            self::F_ADDED_ROLE_ACTIONS_ANNOTATE => $this->object->getAddedRoleActionsAnnotate(),
        ];

        $this->setValuesByArray($array);
    }

    public function saveForm()
    {
        if (!$this->checkInput()) {
            return false;
        }

        $this->object->setDefault($this->getInput(self::F_DEFAULT));
        $this->object->setTitleDE($this->getInput(self::F_TITLE_DE));
        $this->object->setTitleEN($this->getInput(self::F_TITLE_EN));
        $this->object->setInfoDE($this->getInput(self::F_INFO_DE));
        $this->object->setInfoEN($this->getInput(self::F_INFO_EN));
        $this->object->setRole($this->getInput(self::F_ROLE));
        $this->object->setRead($this->getInput(self::F_READ));
        $this->object->setWrite($this->getInput(self::F_WRITE));
        $this->object->setAdditionalAclActions($this->getInput(self::F_ADDITIONAL_ACL_ACTIONS));
        $this->object->setAdditionalActionsDownload($this->getInput(self::F_ADDITIONAL_ACTIONS_DOWNLOAD));
        $this->object->setAdditionalActionsAnnotate($this->getInput(self::F_ADDITIONAL_ACTIONS_ANNOTATE));

        $this->object->setAddedRole($this->getInput(self::F_ADDED_ROLE));

        if ($this->getInput(self::F_ADDED_ROLE)) {
            $this->object->setAddedRoleName($this->getInput(self::F_ADDED_ROLE_NAME));
            $this->object->setAddedRoleRead($this->getInput(self::F_ADDED_ROLE_READ));
            $this->object->setAddedRoleWrite($this->getInput(self::F_ADDED_ROLE_WRITE));
            $this->object->setAddedRoleAclActions($this->getInput(self::F_ADDED_ROLE_ACL_ACTIONS));
            $this->object->setAddedRoleActionsDownload($this->getInput(self::F_ADDED_ROLE_ACTIONS_DOWNLOAD));
            $this->object->setAddedRoleActionsAnnotate($this->getInput(self::F_ADDED_ROLE_ACTIONS_ANNOTATE));
        } else {
            $this->object->setAddedRoleName(null);
            $this->object->setAddedRoleRead(null);
            $this->object->setAddedRoleWrite(null);
            $this->object->setAddedRoleAclActions(null);
            $this->object->setAddedRoleActionsDownload(null);
            $this->object->setAddedRoleActionsAnnotate(null);
        }
        // reset other default template(s) if this one is set as default
        if ($this->getInput(self::F_DEFAULT)) {
            foreach (PermissionTemplate::where(['is_default' => 1])->get() as $default_template) {
                /** @var $default_template PermissionTemplate */
                if ($default_template->getId() != $this->object->getId()) {
                    $default_template->setDefault(0);
                    $default_template->update();
                }
            }
        }

        $this->object->store();

        return true;
    }

    /**
     * @param $lang_var
     *
     * @return string
     */
    protected function txt($lang_var)
    {
        return self::plugin()->getPluginObject()->txt('perm_tpl_form_' . $lang_var);
    }
}
