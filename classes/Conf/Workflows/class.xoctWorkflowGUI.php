<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\LegacyHelpers\OutputTrait;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class xoctWorkflowGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowGUI : xoctMainGUI
 */
class xoctWorkflowGUI extends xoctGUI
{
    use TranslatorTrait;
    use OutputTrait;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const LANG_MODULE = 'workflow';
    public const CMD_SAVE_SETTINGS = 'saveSettings';
    public const CMD_UPDATE_WORKFLOWS = 'updateWorkflows';
    public const CMD_CONFIRM_RESET_WORKFLOWS = 'confirmResetWorkflows';
    public const CMD_RESET_WORKFLOWS = 'resetWorkflows';
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var WorkflowRepository
     */
    protected $workflow_repository;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilLanguage
     */
    private $language;
    /**
     * @var \ILIAS\HTTP\Services
     */
    private $http;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilTabs
     */
    private $tabs;
    /**
     * @var string
     */
    protected $wf_subtab_active;

    public function __construct(WorkflowRepository $workflow_repository)
    {
        global $DIC;
        parent::__construct();
        $ui = $DIC->ui();
        $this->toolbar = $DIC->toolbar();
        $this->language = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->http = $DIC->http();
        $this->workflow_repository = $workflow_repository;
        $this->factory = $ui->factory();
        $this->main_tpl = $ui->mainTemplate();
        $this->setTab();
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function index()
    {
        if ($this->wf_subtab_active === xoctMainGUI::SUBTAB_WORKFLOWS_LIST) {
            $this->initToolbar();
            $table = new xoctWorkflowTableGUI($this, self::CMD_STANDARD, $this->workflow_repository);
            $this->output($table);
        } else {
            $this->output($this->getWorkflowSettingsForm());
        }
    }

    /**
     * Helps setting the tabs at all time.
     */
    public function setTab()
    {
        $this->ctrl->saveParameter($this, 'wf_subtab_active');
        $this->wf_subtab_active = $_GET['wf_subtab_active'] ?: xoctMainGUI::SUBTAB_WORKFLOWS_SETTINGS;
        $this->tabs->setSubTabActive($this->wf_subtab_active);
    }

    public function setTabParameter($tab = xoctMainGUI::SUBTAB_WORKFLOWS_SETTINGS)
    {
        $this->ctrl->setParameter(
            $this,
            'wf_subtab_active',
            $tab
        );
    }

    /**
     *
     */
    protected function getWorkflowSettingsForm()
    {
        $tags = $this->factory->input()->field()->text($this->translate(PluginConfig::F_WORKFLOWS_TAGS))
            ->withByline($this->translate(PluginConfig::F_WORKFLOWS_TAGS . '_info'))
            ->withValue(PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_TAGS) ?? '');
        $excluded_roles = $this->factory->input()->field()->text($this->translate(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES))
            ->withByline($this->translate(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES . '_info'))
            ->withValue(PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES) ?? '');
        return $this->factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, self::CMD_SAVE_SETTINGS),
            [
                $this->factory->input()->field()->section(
                    [
                        PluginConfig::F_WORKFLOWS_TAGS => $tags,
                        PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES => $excluded_roles
                    ],
                    $this->txt('settings_header'),
                    $this->txt('settings_header_description')
                )
            ]
        );
    }

    protected function saveSettings()
    {
        $this->setTabParameter();
        $form = $this->getWorkflowSettingsForm()->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $data = reset($data);

            $current_tags = PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_TAGS);
            $current_roles = PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES);

            $new_tags = $data[PluginConfig::F_WORKFLOWS_TAGS] ?? '';
            $new_roles = $data[PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES] ?? '';

            try {
                $update_succeeded = $this->workflow_repository->updateList($new_tags, $new_roles);
                if ($update_succeeded) {
                    ilUtil::sendSuccess($this->translate('msg_workflow_settings_saved'), true);
                    PluginConfig::set(
                        PluginConfig::F_WORKFLOWS_TAGS,
                        trim($new_tags)
                    );
                    PluginConfig::set(
                        PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES,
                        trim($new_roles)
                    );
                } else {
                    ilUtil::sendFailure($this->translate('msg_workflow_settings_saved_update_failed'), true);
                    // Reverting back!
                    PluginConfig::set(PluginConfig::F_WORKFLOWS_TAGS, $current_tags);
                    PluginConfig::set(PluginConfig::F_WORKFLOWS_EXCLUDE_ROLES, $current_roles);
                }
            } catch (xoctException $e) {
                ilUtil::sendFailure($e->getMessage(), true);
            }
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->output($form);
        }
    }

    protected function initToolbar()
    {
        $update_workflows_button = $this->factory->button()->primary(
            $this->plugin->txt('config_workflows_update_btn'),
            $this->ctrl->getLinkTarget($this, self::CMD_UPDATE_WORKFLOWS)
        );
        $this->toolbar->addComponent($update_workflows_button);
        $reset_workflows_button = $this->factory->button()->standard(
            $this->plugin->txt('config_workflows_reset_btn'),
            $this->ctrl->getLinkTarget($this, self::CMD_CONFIRM_RESET_WORKFLOWS)
        );
        $this->toolbar->addComponent($reset_workflows_button);
    }

    protected function updateWorkflows()
    {
        $this->setTabParameter(xoctMainGUI::SUBTAB_WORKFLOWS_LIST);
        $update_succeeded = $this->workflow_repository->updateList();
        if ($update_succeeded) {
            ilUtil::sendSuccess($this->translate('msg_workflow_list_update_success'), true);
        } else {
            ilUtil::sendFailure($this->translate('msg_workflow_list_update_failed'), true);
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function confirmResetWorkflows()
    {
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->setCancel($this->language->txt('cancel'), self::CMD_STANDARD);
        $ilConfirmationGUI->setConfirm($this->language->txt('confirm'), self::CMD_RESET_WORKFLOWS);
        $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_confirm_reset_workflow_list'));
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    protected function resetWorkflows()
    {
        try {
            $reset_succeeded = $this->workflow_repository->resetList();
            if ($reset_succeeded) {
                ilUtil::sendSuccess($this->translate('msg_workflow_list_reset_success'), true);
            } else {
                ilUtil::sendFailure($this->translate('msg_workflow_list_reset_failed'), true);
            }
        } catch (xoctException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     * @throws DICException
     */
    protected function getForm(WorkflowAR $workflow = null): Standard
    {
        $id = $this->factory->input()->field()->text($this->language->txt('id'))->withDisabled(true);
        $title = $this->factory->input()->field()->text($this->language->txt('title'));
        $description = $this->factory->input()->field()->textarea($this->language->txt('description'));
        $tags = $this->factory->input()->field()->text($this->translate('tags', self::LANG_MODULE))->withDisabled(true);
        $roles = $this->factory->input()->field()->text($this->translate('roles', self::LANG_MODULE))->withDisabled(true);
        $configuration_panel = $this->factory->input()->field()->textarea($this->translate('config_panel', self::LANG_MODULE))
            ->withDisabled(true);

        if (!is_null($workflow)) {
            $this->ctrl->setParameter($this, 'workflow_id', $workflow->getId());
        }
        return $this->factory->input()->container()->form()->standard(
            is_null($workflow) ?
                $this->ctrl->getFormAction($this, self::CMD_CREATE)
                : $this->ctrl->getFormAction($this, self::CMD_UPDATE),
            [
                $this->factory->input()->field()->section(
                    [
                        'id' => is_null($workflow) ? $id : $id->withValue($workflow->getWorkflowId()),
                        'title' => is_null($workflow) ? $title : $title->withValue($workflow->getTitle()),
                        'description' => is_null($workflow) ? $description : $description->withValue($workflow->getDescription()),
                        'tags' => is_null($workflow) ? $tags : $tags->withValue($workflow->getTags()),
                        'roles' => is_null($workflow) ? $roles : $roles->withValue($workflow->getRoles()),
                        'configuration_panel' => is_null($workflow) ? $configuration_panel : $configuration_panel->withValue(
                            json_encode($workflow->getConfigPanel())
                        )
                    ],
                    $this->plugin->txt('workflow')
                )
            ]
        );
    }

    /**
     *
     */
    protected function add()
    {
        $this->output($this->getForm());
    }

    /**
     *
     */
    protected function create()
    {
        $form = $this->getForm()->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $wf = reset($data);
            $this->workflow_repository->createOrUpdate($wf['id'], $wf['title'], $wf['description'], $wf['tags'], $wf['roles']);
            ilUtil::sendSuccess($this->plugin->txt('msg_workflow_created'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->output($form);
        }
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function edit()
    {
        $workflow_id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        $this->output($this->getForm(WorkflowAR::find($workflow_id)));
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function update()
    {
        $id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        $form = $this->getForm(WorkflowAR::find($id))->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $wf = reset($data);
            $this->workflow_repository->createOrUpdate($wf['id'], $wf['title'], $wf['description']);
            ilUtil::sendSuccess($this->plugin->txt('msg_workflow_updated'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->output($form);
        }
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        // not required, using modal
    }

    /**
     * @throws DICException
     */
    protected function delete()
    {
        $items = $this->http->request()->getParsedBody();
        $items = $items['interruptive_items'];
        if (is_array($items) && count($items) === 1) {
            $id = array_shift($items);
            $this->workflow_repository->delete($id);
            ilUtil::sendSuccess($this->plugin->txt('workflow_msg_workflow_deleted'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
    }

    /**
     * @param $key
     *
     * @return string
     * @throws DICException
     */
    public function txt($key): string
    {
        return $this->translate($key, self::LANG_MODULE);
    }
}
