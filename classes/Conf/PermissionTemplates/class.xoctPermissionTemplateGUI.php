<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctPermissionTemplateGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPermissionTemplateGUI: xoctMainGUI
 */
class xoctPermissionTemplateGUI extends xoctGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'config' : $module, $fallback);
    }


    public const IDENTIFIER = 'tpl_id';

    public const SUBTAB_GENERAL = 'general';
    public const SUBTAB_PERMISSION_TEMPLATES = 'permission_templates';

    public const CMD_UPDATE_TEMPLATE = 'updateTemplate';

    protected $subtab_active;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ilLanguage
     */
    private $language;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->language = $DIC->language();
    }

    public function executeCommand(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');

        parent::executeCommand();
    }

    /**
     *
     */
    protected function index(): void
    {
        $this->setSubTabs();

        $this->subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? self::SUBTAB_GENERAL;
        $this->tabs->setSubTabActive($this->subtab_active);
        $this->ctrl->saveParameter($this, 'subtab_active');
        switch ($this->subtab_active) {
            case self::SUBTAB_GENERAL:
                $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
                $xoctVideoPortalSettingsFormGUI->fillForm();
                $this->main_tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
                break;
            case self::SUBTAB_PERMISSION_TEMPLATES:
                $xoctPermissionTemplateTableGUI = new xoctPermissionTemplateTableGUI($this);
                $this->main_tpl->setContent($xoctPermissionTemplateTableGUI->getHTML());
                break;
        }
    }

    /**
     *
     */
    protected function add(): void
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this, new PermissionTemplate());
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function create(): void
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this, new PermissionTemplate());
        $xoctPermissionTemplateFormGUI->setValuesByPost();
        if ($xoctPermissionTemplateFormGUI->saveForm()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function edit(): void
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI(
            $this,
            PermissionTemplate::find($this->http->request()->getQueryParams()[self::IDENTIFIER])
        );
        $xoctPermissionTemplateFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function update(): void
    {
        $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
        $xoctVideoPortalSettingsFormGUI->setValuesByPost();
        if ($xoctVideoPortalSettingsFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->main_tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
    }

    /**
     *
     */
    protected function updateTemplate()
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI(
            $this,
            PermissionTemplate::find((int) $this->http->request()->getQueryParams()[self::IDENTIFIER])
        );
        $xoctPermissionTemplateFormGUI->setValuesByPost();
        if ($xoctPermissionTemplateFormGUI->saveForm()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    protected function setSubTabs()
    {
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
        $this->tabs->addSubTab(
            self::SUBTAB_GENERAL,
            $this->getLocaleString(self::SUBTAB_GENERAL, 'subtab'),
            $this->ctrl->getLinkTarget($this)
        );
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_PERMISSION_TEMPLATES);
        $this->tabs->addSubTab(
            self::SUBTAB_PERMISSION_TEMPLATES,
            $this->getLocaleString(self::SUBTAB_PERMISSION_TEMPLATES, 'subtab'),
            $this->ctrl->getLinkTarget($this)
        );
        $this->ctrl->clearParameters($this);
    }

    /**
     *
     */
    protected function confirmDelete(): void
    {
        $tpl_id = $this->http->request()->getParsedBody()['tpl_id'];
        $template = PermissionTemplate::find($tpl_id);
        $template->delete();
        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function delete(): void
    {
        $this->main_tpl->setOnScreenMessage('question', $this->getLocaleString('msg_confirm_delete_perm_template'));
        $tpl_id = (int) ($this->http->request()->getQueryParams()['tpl_id'] ?? 0);
        $template = PermissionTemplate::find($tpl_id);
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->addItem('tpl_id', $tpl_id, $template->getTitle());
        $ilConfirmationGUI->addButton($this->getLocaleString('delete'), self::CMD_CONFIRM);
        $ilConfirmationGUI->addButton($this->getLocaleString('cancel'), self::CMD_STANDARD);
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    /**
     * ajax
     */
    protected function reorder()
    {
        $ids = $this->http->request()->getParsedBody()['ids'] ?? [];
        $sort = 1;
        foreach ($ids as $id) {
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find((int) $id);
            $perm_tpl->setSort($sort);
            $perm_tpl->update();
            $sort++;
        }
        exit;
    }

    /**
     * @param $key
     */
    public function txt($key): string
    {
        return $this->plugin->txt('config_' . $key);
    }
}
