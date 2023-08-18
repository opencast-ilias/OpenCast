<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;

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
    /**
     * @var PublicationUsageRepository
     */
    protected $repository;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;

    /**
     * xoctPublicationUsageGUI constructor.
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->toolbar = $DIC->toolbar();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->repository = new PublicationUsageRepository();
    }

    /**
     * @throws DICException
     */
    protected function index()
    {
        if ($this->repository->getMissingUsageIds() !== []) {
            $b = ilLinkButton::getInstance();
            $b->setCaption($this->plugin->getPrefix() . '_publication_usage_add_new');
            $b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID));
            $this->toolbar->addButtonInstance($b);
        }
        $xoctPublicationUsageTableGUI = new xoctPublicationUsageTableGUI($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($xoctPublicationUsageTableGUI->getHTML());
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
}
