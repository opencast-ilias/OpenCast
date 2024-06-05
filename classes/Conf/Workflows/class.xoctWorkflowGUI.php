<?php

declare(strict_types=1);
use ILIAS\UI\Renderer;

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctWorkflowGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowGUI : xoctMainGUI
 */
class xoctWorkflowGUI extends xoctGUI
{
    use LocaleTrait;

    public const LANG_MODULE = 'workflow';
    public const CMD_SAVE_SETTINGS = 'saveSettings';
    public const CMD_UPDATE_WORKFLOWS = 'updateWorkflows';
    public const CMD_CONFIRM_RESET_WORKFLOWS = 'confirmResetWorkflows';
    public const CMD_RESET_WORKFLOWS = 'resetWorkflows';
    /**
     * @var Renderer
     */
    private $ui_renderer;
    /**
     * @var Factory
     */
    protected $factory;
    protected WorkflowRepository $workflow_repository;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilLanguage
     */
    private $language;
    /**
     * @var \ilTabsGUI
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
        $this->workflow_repository = $workflow_repository;
        $this->factory = $ui->factory();
        $this->ui_renderer = $ui->renderer();
        $this->wf_subtab_active =
            $this->http->request()->getQueryParams()['wf_subtab_active'] ?? xoctMainGUI::SUBTAB_WORKFLOWS_SETTINGS;
        $this->setTab();
    }

    protected function index(): void
    {
        if ($this->wf_subtab_active === xoctMainGUI::SUBTAB_WORKFLOWS_LIST) {
            $this->initToolbar();
            $table = new xoctWorkflowTableGUI($this, self::CMD_STANDARD, $this->workflow_repository);
            $this->main_tpl->setContent($table->getHTML());
        } else {
            $this->main_tpl->setContent(
                $this->ui_renderer->render($this->getWorkflowSettingsForm())
            );
        }
    }

    /**
     * Helps setting the tabs at all time.
     */
    public function setTab(): void
    {
        $this->ctrl->saveParameter($this, 'wf_subtab_active');
        $this->tabs->setSubTabActive($this->wf_subtab_active);
    }

    public function setTabParameter(string $tab = xoctMainGUI::SUBTAB_WORKFLOWS_SETTINGS): void
    {
        $this->ctrl->setParameter(
            $this,
            'wf_subtab_active',
            $tab
        );
    }

    protected function getWorkflowSettingsForm(): Standard
    {
        $tags = $this->factory->input()->field()->text($this->getLocaleString(PluginConfig::F_WORKFLOWS_TAGS))
                              ->withByline($this->getLocaleString(PluginConfig::F_WORKFLOWS_TAGS . '_info'))
                              ->withValue(PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_TAGS) ?? '');
        return $this->factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, self::CMD_SAVE_SETTINGS),
            [
                $this->factory->input()->field()->section(
                    [
                        PluginConfig::F_WORKFLOWS_TAGS => $tags,
                    ],
                    $this->getLocaleString('settings_header', self::LANG_MODULE),
                    $this->getLocaleString('settings_header_description', self::LANG_MODULE)
                )
            ]
        );
    }

    protected function saveSettings(): void
    {
        $this->setTabParameter();
        $form = $this->getWorkflowSettingsForm()->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $data = reset($data);

            $current_tags = PluginConfig::getConfig(PluginConfig::F_WORKFLOWS_TAGS);

            $new_tags = $data[PluginConfig::F_WORKFLOWS_TAGS] ?? '';

            try {
                $update_succeeded = $this->workflow_repository->updateList($new_tags);
                if ($update_succeeded) {
                    $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_workflow_settings_saved'), true);
                    PluginConfig::set(
                        PluginConfig::F_WORKFLOWS_TAGS,
                        trim($new_tags)
                    );
                } else {
                    $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('msg_workflow_settings_saved_update_failed'), true);
                    // Reverting back!
                    PluginConfig::set(PluginConfig::F_WORKFLOWS_TAGS, $current_tags);
                }
            } catch (xoctException $e) {
                $this->main_tpl->setOnScreenMessage('failure', $e->getMessage(), true);
            }
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->main_tpl->setContent(
                $this->ui_renderer->render($form)
            );
        }
    }

    protected function initToolbar(): void
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

    protected function updateWorkflows(): void
    {
        $this->setTabParameter(xoctMainGUI::SUBTAB_WORKFLOWS_LIST);
        $update_succeeded = $this->workflow_repository->updateList();
        if ($update_succeeded) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_workflow_list_update_success'), true);
        } else {
            $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('msg_workflow_list_update_failed'), true);
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function confirmResetWorkflows(): void
    {
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->setCancel($this->language->txt('cancel'), self::CMD_STANDARD);
        $ilConfirmationGUI->setConfirm($this->language->txt('confirm'), self::CMD_RESET_WORKFLOWS);
        $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_confirm_reset_workflow_list'));
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    protected function resetWorkflows(): void
    {
        try {
            $reset_succeeded = $this->workflow_repository->resetList();
            if ($reset_succeeded) {
                $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_workflow_list_reset_success'), true);
            } else {
                $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('msg_workflow_list_reset_failed'), true);
            }
        } catch (xoctException $e) {
            $this->main_tpl->setOnScreenMessage('failure', $e->getMessage(), true);
        }
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function getForm(WorkflowAR $workflow = null): Standard
    {
        $id = $this->factory->input()->field()->text($this->language->txt('id'))->withDisabled(true);
        $title = $this->factory->input()->field()->text($this->language->txt('title'));
        $description = $this->factory->input()->field()->textarea($this->language->txt('description'));
        $tags = $this->factory->input()->field()->text($this->getLocaleString('tags', self::LANG_MODULE))->withDisabled(true);
        $configuration_panel = $this->factory->input()->field()->textarea(
            $this->getLocaleString('config_panel', self::LANG_MODULE)
        )
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
                        'description' => is_null($workflow) ? $description : $description->withValue(
                            $workflow->getDescription()
                        ),
                        'tags' => is_null($workflow) ? $tags : $tags->withValue($workflow->getTags()),
                        'configuration_panel' => is_null(
                            $workflow
                        ) ? $configuration_panel : $configuration_panel->withValue(
                            json_encode($workflow->getConfigPanel())
                        )
                    ],
                    $this->plugin->txt('workflow')
                )
            ]
        );
    }


    protected function add(): void
    {
        $this->main_tpl->setContent(
            $this->ui_renderer->render($this->getForm())
        );
    }

    protected function create(): void
    {
        $form = $this->getForm()->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $wf = reset($data);
            $this->workflow_repository->createOrUpdate($wf['id'], $wf['title'], $wf['description'], $wf['tags']);
            $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_workflow_created'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->ui_renderer->render($form);
        }
    }

    protected function edit(): void
    {
        $workflow_id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        $this->main_tpl->setContent(
            $this->ui_renderer->render(
                $this->getForm(WorkflowAR::find($workflow_id))
            )
        );
    }

    protected function update(): void
    {
        $id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        $form = $this->getForm(WorkflowAR::find($id))->withRequest($this->http->request());
        if ($data = $form->getData()) {
            $wf = reset($data);
            $this->workflow_repository->createOrUpdate($wf['id'], $wf['title'], $wf['description']);
            $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_workflow_updated'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        } else {
            $this->main_tpl->setContent(
                $this->ui_renderer->render($form)
            );
        }
    }


    protected function confirmDelete(): void
    {
        // not required, using modal
    }

    protected function delete(): void
    {
        $items = $this->http->request()->getParsedBody();
        $items = $items['interruptive_items'];
        if (is_array($items) && count($items) === 1) {
            $id = array_shift($items);
            $this->workflow_repository->delete($id);
            $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('workflow_msg_workflow_deleted'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
    }

    public function txt(string $key): string
    {
        return $this->getLocaleString($key, self::LANG_MODULE);
    }
}
