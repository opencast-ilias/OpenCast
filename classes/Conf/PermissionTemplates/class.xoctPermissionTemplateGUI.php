<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
/**
 * Class xoctPermissionTemplateGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPermissionTemplateGUI: xoctMainGUI
 */
class xoctPermissionTemplateGUI extends xoctGUI {

	const IDENTIFIER = 'tpl_id';
	
	const SUBTAB_GENERAL = 'general';
	const SUBTAB_PERMISSION_TEMPLATES = 'permission_templates';

	const CMD_UPDATE_TEMPLATE = 'updateTemplate';

	protected $subtab_active;

    public function executeCommand()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');

        parent::executeCommand();
    }


    /**
	 *
	 */
	protected function index() {
        $this->setSubTabs();

        $this->subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : self::SUBTAB_GENERAL;
        $this->tabs->setSubTabActive($this->subtab_active);
        $this->ctrl->saveParameter($this, 'subtab_active');
        switch ($this->subtab_active) {
            case self::SUBTAB_GENERAL:
                $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
                $xoctVideoPortalSettingsFormGUI->fillForm();
                $this->tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
                break;
            case self::SUBTAB_PERMISSION_TEMPLATES:
                $xoctPermissionTemplateTableGUI = new xoctPermissionTemplateTableGUI($this);
                $this->tpl->setContent($xoctPermissionTemplateTableGUI->getHTML());
                break;
        }
	}


	/**
	 *
	 */
	protected function add() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,new xoctPermissionTemplate());
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function create() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,new xoctPermissionTemplate());
		$xoctPermissionTemplateFormGUI->setValuesByPost();
		if ($xoctPermissionTemplateFormGUI->saveForm()) {
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function edit() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,xoctPermissionTemplate::find($_GET[self::IDENTIFIER]));
		$xoctPermissionTemplateFormGUI->fillForm();
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}

    /**
     *
     */
    protected function update() {
        $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
        $xoctVideoPortalSettingsFormGUI->setValuesByPost();
        if ($xoctVideoPortalSettingsFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
    }


	/**
	 *
	 */
	protected function updateTemplate() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,xoctPermissionTemplate::find($_GET[self::IDENTIFIER]));
		$xoctPermissionTemplateFormGUI->setValuesByPost();
		if ($xoctPermissionTemplateFormGUI->saveForm()) {
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}

    protected function setSubTabs() {
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
        $this->tabs->addSubTab(self::SUBTAB_GENERAL, $this->pl->txt('subtab_' . self::SUBTAB_GENERAL), $this->ctrl->getLinkTarget($this));
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_PERMISSION_TEMPLATES);
        $this->tabs->addSubTab(self::SUBTAB_PERMISSION_TEMPLATES, $this->pl->txt('subtab_' . self::SUBTAB_PERMISSION_TEMPLATES), $this->ctrl->getLinkTarget($this));
        $this->ctrl->clearParameters($this);
    }


	/**
	 *
	 */
	protected function confirmDelete() {
        $tpl_id = $_POST['tpl_id'];
        $template = xoctPermissionTemplate::find($tpl_id);
        $template->delete();
        ilUtil::sendSuccess($this->pl->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
	}


	/**
	 *
	 */
	protected function delete() {
	    ilUtil::sendQuestion($this->pl->txt('msg_confirm_delete_perm_template'));
		$tpl_id = $_GET['tpl_id'];
		$template = xoctPermissionTemplate::find($tpl_id);
		$ilConfirmationGUI = new ilConfirmationGUI();
		$ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
		$ilConfirmationGUI->addItem('tpl_id', $tpl_id, $template->getTitle());
		$ilConfirmationGUI->addButton($this->lng->txt('delete'), self::CMD_CONFIRM);
		$ilConfirmationGUI->addButton($this->lng->txt('cancel'), self::CMD_STANDARD);
		$this->tpl->setContent($ilConfirmationGUI->getHTML());
	}


	/**
	 * ajax
	 */
	protected function reorder() {
		$ids = $_POST['ids'];
		$sort = 1;
		foreach ($ids as $id) {
			/** @var xoctPermissionTemplate $perm_tpl */
			$perm_tpl = xoctPermissionTemplate::find($id);
			$perm_tpl->setSort($sort);
			$perm_tpl->update();
			$sort++;
		}
		exit;
	}

    /**
     * @param $key
     *
     * @return string
     */
    public function txt($key) {
        return $this->pl->txt('config_' . $key);
    }
}