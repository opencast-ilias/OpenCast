<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;

/**
 * Class xoctPublicationUsageGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPublicationUsageGUI : xoctMainGUI
 */
class xoctPublicationUsageGUI extends xoctGUI
{
    public const IDENTIFIER = 'usage_id';
    public const CMD_SELECT_PUBLICATION_ID = 'selectPublicationId';
    public const CMD_SELECT_PUBLICATION_ID_SUB = 'selectPublicationIdForSub';
    public const CMD_ADD_SUB = 'addSub';
    public const CMD_CREATE_SUB = 'createSub';
    public const CMD_EDIT_SUB = 'editSub';
    public const CMD_UPDATE_SUB = 'updateSub';
    public const CMD_DELETE_SUB = 'deleteSub';
    public const CMD_CONFIRM_DELETE_SUB = 'confirmDeleteSub';
    public const CMD_ADD_NEW_GROUP = 'addNewGroup';
    public const CMD_CREATE_NEW_GROUP = 'createGroup';
    public const CMD_EDIT_GROUP = 'editGroup';
    public const CMD_UPDATE_GROUP = 'updateGroup';
    public const CMD_DELETE_GROUP = 'deleteGroup';
    public const CMD_CONFIRM_DELETE_GROUP = 'confirmDeleteGroup';
    /**
     * @var PublicationUsageRepository
     */
    protected $repository;
    /**
     * @var string
     */
    protected $pub_subtab_active;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilTabs
     */
    private $tabs;

    /**
     * xoctPublicationUsageGUI constructor.
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->toolbar = $DIC->toolbar();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->repository = new PublicationUsageRepository();
        $this->setTab();
    }

    /**
     * @throws DICException
     */
    protected function index()
    {
        $xoctPublicationTabsTableGUI = $this->initTabTableGUI($this->pub_subtab_active);
        $this->main_tpl->setContent($xoctPublicationUsageTableGUI->getHTML());
    }

    /**
     * Helps setting the tabs at all time.
     */
    public function setTab()
    {
        $this->ctrl->saveParameter($this, 'pub_subtab_active');
        $this->pub_subtab_active = $_GET['pub_subtab_active'] ?: xoctMainGUI::SUBTAB_PUBLICATION_USAGE;
        $this->tabs->setSubTabActive($this->pub_subtab_active);
    }


    /**
     * Decides which content to display for the current tab.
     * @return ilTable2GUI
     */
    protected function initTabTableGUI($pub_subtab_active): ilTable2GUI
    {
        if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_USAGE) {
            if (count($this->repository->getMissingUsageIds()) > 0) {
                $b = ilLinkButton::getInstance();
                $b->setCaption($this->plugin->getPluginObject()->getPrefix() . '_publication_usage_add_new');
                $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID));
                $this->toolbar->addButtonInstance($b);
            }
            return new xoctPublicationUsageTableGUI($this, self::CMD_STANDARD);
        } else if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_SUB_USAGE) {
            if (count($this->repository->getSubAllowedUsageIds()) > 0) {
                $b = ilLinkButton::getInstance();
                $b->setCaption($this->plugin->getPluginObject()->getPrefix() . '_publication_usage_add_new_sub');
                $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID_SUB));
                $this->toolbar->addButtonInstance($b);
            }
            return new xoctPublicationSubUsageTableGUI($this, self::CMD_STANDARD);
        } else if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_GROUPS) {
            $b = ilLinkButton::getInstance();
            $b->setCaption($this->plugin->getPluginObject()->getPrefix() . '_publication_usage_add_new_group');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD_NEW_GROUP));
            $this->toolbar->addButtonInstance($b);
            return new xoctPublicationGroupTableGUI($this, self::CMD_STANDARD);
        }
    }

    /**
     *
     */
    protected function selectPublicationId()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('select_usage_id'));
        $form->addCommandButton(self::CMD_ADD, $this->txt(self::CMD_ADD));
        $form->addCommandButton(self::CMD_CANCEL, $this->txt(self::CMD_CANCEL));
        $sel = new ilSelectInputGUI(
            $this->txt(xoctPublicationUsageFormGUI::F_CHANNEL),
            xoctPublicationUsageFormGUI::F_CHANNEL
        );
        $options = [];
        foreach ($this->repository->getMissingUsageIds() as $id) {
            $options[$id] = $this->txt('type_' . $id);
        }
        $sel->setOptions($options);

        $form->addItem($sel);
        $this->main_tpl->setContent($form->getHTML());
    }

    /**
     *
     */
    protected function add()
    {
        if (!$_POST[xoctPublicationUsageFormGUI::F_CHANNEL]) {
            $this->ctrl->redirect($this, self::CMD_SELECT_PUBLICATION_ID);
        }
        $xoctPublicationUsage = new PublicationUsage();
        $xoctPublicationUsage->setUsageId($_POST[xoctPublicationUsageFormGUI::F_CHANNEL]);
        $xoctPublicationUsage->setTitle($this->txt('type_' . $_POST[xoctPublicationUsageFormGUI::F_CHANNEL]));
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, $xoctPublicationUsage);
        $xoctPublicationUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    /**
     * @throws DICException
     */
    protected function create()
    {
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, new PublicationUsage());
        $xoctPublicationUsageFormGUI->setValuesByPost();
        if ($xoctPublicationUsageFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->txt('publication_usage_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    /**
     *
     */
    protected function edit()
    {
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI(
            $this,
            $this->repository->getUsage($_GET[self::IDENTIFIER])
        );
        $xoctPublicationUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    /**
     * @throws DICException
     */
    protected function update()
    {
        $usage_id = $_GET[self::IDENTIFIER];
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI(
            $this,
            $usage_id ? $this->repository->getUsage($_GET[self::IDENTIFIER]) : new PublicationUsage()
        );
        $xoctPublicationUsageFormGUI->setValuesByPost();
        if ($xoctPublicationUsageFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->txt('publication_usage_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    /**
     * @param $key
     *
     * @throws DICException
     */
    public function txt($key): string
    {
        return $this->plugin->txt('publication_usage_' . $key);
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        $xoctPublicationUsage = $this->repository->getUsage($_GET[self::IDENTIFIER]);
        $confirm = new ilConfirmationGUI();
        $confirm->addItem(self::IDENTIFIER, $xoctPublicationUsage->getUsageId(), $xoctPublicationUsage->getTitle());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    /**
     *
     */
    protected function delete()
    {
        $this->repository->delete($_POST[self::IDENTIFIER]);
        $this->cancel();
    }


    ### Subs Section ###
    /**
     * Helps select the sub usage channel.
     * INFO: Although there is only Download channel available to select, but there is the capability to extend this feature
     * for other channels too.
     */
    protected function selectPublicationIdForSub()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('select_sub_usage_id'));
        $form->setDescription($this->txt('select_sub_usage_id_desc'));
        $form->addCommandButton(self::CMD_ADD_SUB, $this->txt(self::CMD_ADD));
        $form->addCommandButton(self::CMD_CANCEL, $this->txt(self::CMD_CANCEL));
        $sel = new ilSelectInputGUI($this->txt(xoctPublicationUsageFormGUI::F_CHANNEL), xoctPublicationUsageFormGUI::F_CHANNEL);
        $options = [];
        foreach ($this->repository->getSubAllowedUsageIds() as $id) {
            $options[$id] = $this->txt('type_' . $id);
        }
        $sel->setOptions($options);

        $form->addItem($sel);
        $this->main_tpl->setContent($form->getHTML());
    }

    /**
     *
     */
    protected function addSub()
    {
        $channel = $_POST[xoctPublicationUsageFormGUI::F_CHANNEL];
        if (empty($channel) || !in_array($channel, $this->repository->getSubAllowedUsageIds())) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_sub_not_allowed'), true);
            $this->ctrl->redirect($this, self::CMD_SELECT_PUBLICATION_ID_SUB);
        }
        $xoctPublicationSubUsage = new PublicationSubUsage();
        $xoctPublicationSubUsage->setParentUsageId($channel);
        $title_text = $this->txt('type_' . $channel);
        $title = PublicationSubUsageRepository::generateTitle($channel, $title_text);
        $xoctPublicationSubUsage->setTitle($title);
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, $xoctPublicationSubUsage);
        $xoctPublicationSubUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    /**
     * @throws DICException
     */
    protected function createSub()
    {
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, new PublicationSubUsage());
        $xoctPublicationSubUsageFormGUI->setValuesByPost();
        if ($xoctPublicationSubUsageFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->translate('publication_usage_msg_success_sub'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    /**
     *
     */
    protected function editSub()
    {
        if (!PublicationSubUsage::find($_GET['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, PublicationSubUsage::find($_GET['id']), false);
        $xoctPublicationSubUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }


    /**
     * @throws DICException
     */
    protected function updateSub()
    {
        $sub_usage_id = $_GET['id'];
        if (!PublicationSubUsage::find($sub_usage_id)) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, PublicationSubUsage::find($sub_usage_id), false);
        $xoctPublicationSubUsageFormGUI->setValuesByPost();
        if ($xoctPublicationSubUsageFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->getPluginObject()->txt('publication_usage_msg_success_sub'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    protected function confirmDeleteSub()
    {
        if (!PublicationSubUsage::find($_GET['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsage = PublicationSubUsage::find($_GET['id']);
        $confirm = new ilConfirmationGUI();
        $confirm->setHeaderText($this->txt('confirm_delete_text_sub'));
        $confirm->addItem('id', $_GET['id'], $xoctPublicationSubUsage->getTitle());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE_SUB);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    protected function deleteSub()
    {
        if (!PublicationSubUsage::find($_POST['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_sub_not_found'), true);
            $this->ctrl->redirect($this);
        }

        $xoctPublicationSubUsage = PublicationSubUsage::find($_POST['id']);
        $xoctPublicationSubUsage->delete();
        $this->cancel();
    }

    ### End Subs Section ###

    ### Group Section ###

    protected function addNewGroup()
    {
        $xoctPublicationUsageGroup = new PublicationUsageGroup();
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, $xoctPublicationUsageGroup);
        $xoctPublicationGroupFormGUI->setFormAction($this->ctrl->getFormAction($this));
        $xoctPublicationGroupFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function createGroup()
    {
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, new PublicationUsageGroup());
        $xoctPublicationGroupFormGUI->setValuesByPost();
        if ($xoctPublicationGroupFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->translate('publication_usage_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function editGroup()
    {
        if (!PublicationUsageGroup::find($_GET['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_group_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, PublicationUsageGroup::find($_GET['id']), false);
        $xoctPublicationGroupFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function updateGroup()
    {
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, PublicationUsageGroup::find($_GET['id']), false);
        $xoctPublicationGroupFormGUI->setValuesByPost();
        if ($xoctPublicationGroupFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->plugin->getPluginObject()->txt('publication_usage_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function confirmDeleteGroup()
    {
        if (!PublicationUsageGroup::find($_GET['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_group_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationUsageGroup = PublicationUsageGroup::find($_GET['id']);
        $confirm = new ilConfirmationGUI();
        $confirm->setHeaderText($this->txt('confirm_delete_text_group'));
        $confirm->addItem('id', $_GET['id'], $xoctPublicationUsageGroup->getName());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE_GROUP);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    protected function deleteGroup()
    {
        if (!PublicationUsageGroup::find($_POST['id'])) {
            ilUtil::sendFailure($this->plugin->translate('publication_usage_group_not_found'), true);
            $this->ctrl->redirect($this);
        }

        $xoctPublicationUsageGroup = PublicationUsageGroup::find($_POST['id']);
        $xoctPublicationUsageGroup->delete();
        $this->cancel();
    }
    ### End Group Section ###
}
