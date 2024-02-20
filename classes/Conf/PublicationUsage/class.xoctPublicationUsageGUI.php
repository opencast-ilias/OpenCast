<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctPublicationUsageGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPublicationUsageGUI : xoctMainGUI
 */
class xoctPublicationUsageGUI extends xoctGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'publication_usage' : $module, $fallback);
    }

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
     * @var PublicationSubUsageRepository
     */
    protected $sub_repository;
    /**
     * @var string
     */
    protected $pub_subtab_active;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var int
     */
    protected $get_id;
    /**
     * @var int
     */
    protected $post_id;
    /**
     * @var string
     */
    protected $channel;

    /**
     * xoctPublicationUsageGUI constructor.
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->toolbar = $DIC->toolbar();
        $this->tabs = $DIC->tabs();
        // Getting Query Parameters
        $this->pub_subtab_active =
            $this->http->request()->getQueryParams()['pub_subtab_active'] ?? xoctMainGUI::SUBTAB_PUBLICATION_USAGE;
        $this->identifier = $this->http->request()->getQueryParams()[self::IDENTIFIER] ?? '';
        if (!empty($this->http->request()->getParsedBody()[self::IDENTIFIER])) {
            $this->identifier = $this->http->request()->getParsedBody()[self::IDENTIFIER];
        }
        $this->get_id = (int) ($this->http->request()->getQueryParams()['id'] ?? null);
        $this->post_id = (int) ($this->http->request()->getParsedBody()['id'] ?? null);
        $this->channel = $this->http->request()->getParsedBody()[xoctPublicationUsageFormGUI::F_CHANNEL] ?? null;
        $this->repository = new PublicationUsageRepository();
        $this->sub_repository = new PublicationSubUsageRepository();
        $this->setTab();
    }

    protected function index(): void
    {
        $table = $this->initTabTableGUI($this->pub_subtab_active);
        if ($table !== null) {
            $this->main_tpl->setContent($table->getHTML());
        }
    }

    /**
     * Helps setting the tabs at all time.
     */
    public function setTab(): void
    {
        $this->ctrl->saveParameter($this, 'pub_subtab_active');
        $this->tabs->setSubTabActive($this->pub_subtab_active);
    }

    /**
     * Decides which content to display for the current tab.
     */
    protected function initTabTableGUI(string $pub_subtab_active): ?ilTable2GUI
    {
        if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_USAGE) {
            if (count($this->repository->getMissingUsageIds()) > 0) {
                $b = ilLinkButton::getInstance();
                $b->setCaption($this->plugin->getPrefix() . '_publication_usage_add_new');
                $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID));
                $this->toolbar->addButtonInstance($b);
            }
            return new xoctPublicationUsageTableGUI($this, self::CMD_STANDARD);
        }

        if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_SUB_USAGE) {
            if (count($this->repository->getSubAllowedUsageIds()) > 0) {
                $b = ilLinkButton::getInstance();
                $b->setCaption($this->plugin->getPrefix() . '_publication_usage_add_new_sub');
                $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID_SUB));
                $this->toolbar->addButtonInstance($b);
            }
            return new xoctPublicationSubUsageTableGUI($this, self::CMD_STANDARD);
        }

        if ($pub_subtab_active === xoctMainGUI::SUBTAB_PUBLICATION_GROUPS) {
            $b = ilLinkButton::getInstance();
            $b->setCaption($this->plugin->getPrefix() . '_publication_usage_add_new_group');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD_NEW_GROUP));
            $this->toolbar->addButtonInstance($b);
            return new xoctPublicationGroupTableGUI($this, self::CMD_STANDARD);
        }
        return null;
    }

    protected function selectPublicationId()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->getLocaleString('select_usage_id', 'publication_usage'));
        $form->addCommandButton(self::CMD_ADD, $this->getLocaleString(self::CMD_ADD, 'publication_usage'));
        $form->addCommandButton(self::CMD_CANCEL, $this->getLocaleString(self::CMD_CANCEL, 'publication_usage'));
        $sel = new ilSelectInputGUI(
            $this->getLocaleString(xoctPublicationUsageFormGUI::F_CHANNEL),
            xoctPublicationUsageFormGUI::F_CHANNEL
        );
        $options = [];
        foreach ($this->repository->getMissingUsageIds() as $id) {
            $options[$id] = $this->getLocaleString('type_' . $id);
        }
        $sel->setOptions($options);

        $form->addItem($sel);
        $this->main_tpl->setContent($form->getHTML());
    }

    protected function add(): void
    {
        if (!$this->channel) {
            $this->ctrl->redirect($this, self::CMD_SELECT_PUBLICATION_ID);
        }
        $xoctPublicationUsage = new PublicationUsage();
        $xoctPublicationUsage->setUsageId($this->channel);
        $xoctPublicationUsage->setTitle($this->getLocaleString('type_' . $this->channel));
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, $xoctPublicationUsage);
        $xoctPublicationUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    protected function create(): void
    {
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, new PublicationUsage());
        $xoctPublicationUsageFormGUI->setValuesByPost();
        if ($xoctPublicationUsageFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    protected function edit(): void
    {
        if (empty($this->identifier)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('no_identifier'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI(
            $this,
            $this->repository->getUsage($this->identifier)
        );
        $xoctPublicationUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    protected function update(): void
    {
        $publication_usage = new PublicationUsage();
        if (!$this->identifier && $this->repository->getUsage($this->identifier)) {
            $publication_usage = $this->repository->getUsage($this->identifier);
        }
        $xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI(
            $this,
            $publication_usage
        );
        $xoctPublicationUsageFormGUI->setValuesByPost();
        if ($xoctPublicationUsageFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
    }

    protected function confirmDelete(): void
    {
        if (empty($this->identifier)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('no_identifier'), true);
            $this->ctrl->redirect($this);
        }
        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        $xoctPublicationUsage = $this->repository->getUsage($this->identifier);
        $confirm = new ilConfirmationGUI();
        $confirm->addItem(self::IDENTIFIER, $xoctPublicationUsage->getUsageId(), $xoctPublicationUsage->getTitle());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->getLocaleString(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->getLocaleString(self::CMD_DELETE), self::CMD_DELETE);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    protected function delete(): void
    {
        $this->repository->delete($this->identifier);
        $this->cancel();
    }


    ### Subs Section ###

    /**
     * Helps select the sub usage channel.
     * INFO: Although there is only Download channel available to select, but there is the capability to extend this feature
     * for other channels too.
     */
    protected function selectPublicationIdForSub(): void
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->getLocaleString('select_sub_usage_id'));
        $form->setDescription($this->getLocaleString('select_sub_usage_id_desc'));
        $form->addCommandButton(self::CMD_ADD_SUB, $this->getLocaleString(self::CMD_ADD));
        $form->addCommandButton(self::CMD_CANCEL, $this->getLocaleString(self::CMD_CANCEL));
        $sel = new ilSelectInputGUI(
            $this->getLocaleString(xoctPublicationUsageFormGUI::F_CHANNEL),
            xoctPublicationUsageFormGUI::F_CHANNEL
        );
        $options = [];
        foreach ($this->repository->getSubAllowedUsageIds() as $id) {
            $options[$id] = $this->getLocaleString('type_' . $id);
        }
        $sel->setOptions($options);

        $form->addItem($sel);
        $this->main_tpl->setContent($form->getHTML());
    }

    protected function addSub(): void
    {
        $channel = $this->channel;
        if (empty($channel) || !in_array($channel, $this->repository->getSubAllowedUsageIds(), true)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('sub_not_allowed'), true);
            $this->ctrl->redirect($this, self::CMD_SELECT_PUBLICATION_ID_SUB);
        }
        $xoctPublicationSubUsage = new PublicationSubUsage();
        $xoctPublicationSubUsage->setParentUsageId($channel);
        $title_text = $this->getLocaleString('type_' . $channel);
        $title = $this->sub_repository->generateTitle($channel, $title_text);
        $xoctPublicationSubUsage->setTitle($title);
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, $xoctPublicationSubUsage);
        $xoctPublicationSubUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    protected function createSub(): void
    {
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI($this, new PublicationSubUsage());
        $xoctPublicationSubUsageFormGUI->setValuesByPost();
        if ($xoctPublicationSubUsageFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success_sub'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    protected function editSub(): void
    {
        if (!PublicationSubUsage::find($this->get_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI(
            $this,
            PublicationSubUsage::find($this->get_id),
            false
        );
        $xoctPublicationSubUsageFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    protected function updateSub(): void
    {
        $sub_usage_id = $this->get_id;
        if (!PublicationSubUsage::find($sub_usage_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsageFormGUI = new xoctPublicationSubUsageFormGUI(
            $this,
            PublicationSubUsage::find($sub_usage_id),
            false
        );
        $xoctPublicationSubUsageFormGUI->setValuesByPost();
        if ($xoctPublicationSubUsageFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success_sub'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationSubUsageFormGUI->getHTML());
    }

    protected function confirmDeleteSub(): void
    {
        if (!PublicationSubUsage::find($this->get_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('sub_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationSubUsage = PublicationSubUsage::find($this->get_id);
        $confirm = new ilConfirmationGUI();
        $confirm->setHeaderText($this->getLocaleString('confirm_delete_text_sub'));
        $confirm->addItem('id', $this->get_id, $xoctPublicationSubUsage->getTitle());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->getLocaleString(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->getLocaleString(self::CMD_DELETE), self::CMD_DELETE_SUB);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    protected function deleteSub(): void
    {
        if (!PublicationSubUsage::find($this->post_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('sub_not_found'), true);
            $this->ctrl->redirect($this);
        }

        $xoctPublicationSubUsage = PublicationSubUsage::find($this->post_id);
        $xoctPublicationSubUsage->delete();
        $this->cancel();
    }

    ### End Subs Section ###

    ### Group Section ###

    protected function addNewGroup(): void
    {
        $xoctPublicationUsageGroup = new PublicationUsageGroup();
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, $xoctPublicationUsageGroup);
        $xoctPublicationGroupFormGUI->setFormAction($this->ctrl->getFormAction($this));
        $xoctPublicationGroupFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function createGroup(): void
    {
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI($this, new PublicationUsageGroup());
        $xoctPublicationGroupFormGUI->setValuesByPost();
        if ($xoctPublicationGroupFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function editGroup(): void
    {
        if (!PublicationUsageGroup::find($this->get_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('group_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI(
            $this,
            PublicationUsageGroup::find($this->get_id),
            false
        );
        $xoctPublicationGroupFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function updateGroup(): void
    {
        $xoctPublicationGroupFormGUI = new xoctPublicationGroupFormGUI(
            $this,
            PublicationUsageGroup::find($this->get_id),
            false
        );
        $xoctPublicationGroupFormGUI->setValuesByPost();
        if ($xoctPublicationGroupFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPublicationGroupFormGUI->getHTML());
    }

    protected function confirmDeleteGroup(): void
    {
        if (!PublicationUsageGroup::find($this->get_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('group_not_found'), true);
            $this->ctrl->redirect($this);
        }
        $xoctPublicationUsageGroup = PublicationUsageGroup::find($this->get_id);
        $confirm = new ilConfirmationGUI();
        $confirm->setHeaderText($this->getLocaleString('confirm_delete_text_group'));
        $confirm->addItem('id', $this->get_id, $xoctPublicationUsageGroup->getName());
        $confirm->setFormAction($this->ctrl->getFormAction($this));
        $confirm->setCancel($this->getLocaleString(self::CMD_CANCEL), self::CMD_CANCEL);
        $confirm->setConfirm($this->getLocaleString(self::CMD_DELETE), self::CMD_DELETE_GROUP);

        $this->main_tpl->setContent($confirm->getHTML());
    }

    protected function deleteGroup(): void
    {
        if (!PublicationUsageGroup::find($this->post_id)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('group_not_found'), true);
            $this->ctrl->redirect($this);
        }

        $xoctPublicationUsageGroup = PublicationUsageGroup::find($this->post_id);
        $xoctPublicationUsageGroup->delete();
        $this->cancel();
    }
    ### End Group Section ###
}
