<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Modal;

use ILIAS\DI\Container;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Modal\RoundTrip;
use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;

/**
 * Responsible for building modals.
 */
class EventModals
{
    /**
     * @var Modal
     */
    protected $report_quality_modal;
    /**
     * @var Modal
     */
    protected $report_date_modal;
    /**
     * @var Modal
     */
    protected $startworkflow_modal;

    private $parent_gui;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var WorkflowRepository
     */
    private $workflow_repository;
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;

    public function __construct(
        $parent_gui,
        ilOpenCastPlugin $plugin,
        Container $dic,
        WorkflowRepository $workflow_repository
    ) {
        $this->parent_gui = $parent_gui;
        $this->dic = $dic;
        $this->workflow_repository = $workflow_repository;
        $this->plugin = $plugin;
    }

    public function initWorkflows(): void
    {
        if ($this->workflow_repository->anyWorkflowAvailable()) {
            $tpl = new ilTemplate("tpl.startworkflow_modal.html", true, true, $this->plugin->getDirectory());

            $form_id = 'startworkflow_modal_form';
            $form_submit_btn_id = 'startworkflow-form-submit-btn';
            $tpl->setVariable('FORM_SUBMIT_BTN_ID', $form_submit_btn_id);
            $tpl->setVariable('FORM_ID', $form_id);
            $tpl->setVariable(
                'FORM_ACTION',
                $this->dic->ctrl()->getFormAction($this->parent_gui, $this->parent_gui::CMD_START_WORKFLOW)
            );

            $workflow_options = $this->workflow_repository->buildWorkflowSelectOptions();
            $tpl->setVariable('WORKFLOW_OPTIONS', $workflow_options);

            // Descriptions.
            $description_section_tpl = new ilTemplate(
                "tpl.startworkflow_description_section.html",
                true,
                true,
                $this->plugin->getDirectory()
            );
            $description_blocks = [];
            $workflow_selection_array = $this->workflow_repository->getWorkflowSelectionArray();
            foreach ($this->workflow_repository->getFilteredWorkflowsArray() as $workflow) {
                $description_block_tpl = new ilTemplate(
                    "tpl.startworkflow_description_block.html",
                    true,
                    true,
                    $this->plugin->getDirectory()
                );
                $description = $workflow->getDescription();
                $id = $workflow->getId();
                $header = $workflow_selection_array[$id] ?? $this->plugin->txt('workflow_description_section_header');
                if (!empty(trim($description))) {
                    $description_block_tpl->setVariable('BLOCK_ID', $id);
                    $description_block_tpl->setVariable('HEADER', $header);
                    $description_block_tpl->setVariable('DESCRIPTION_TEXT', $description);
                    $description_blocks[] = $description_block_tpl->get();
                }
            }
            if (!empty($description_blocks)) {
                $description_section_tpl->setVariable('BLOCK_CONTENT', implode('', $description_blocks));
                $tpl->setVariable('WORKFLOW_DESCRIPTIONS', $description_section_tpl->get());
            }

            // Configuration Panel
            $configpanel_section_tpl = new ilTemplate(
                "tpl.startworkflow_configpanel_section.html",
                true,
                true,
                $this->plugin->getDirectory()
            );
            $configpanel_section_tpl->setVariable(
                'HEADER',
                $this->plugin->txt('workflow_configpanel_section_header')
            );
            $configpanel_blocks = [];
            foreach ($this->workflow_repository->parseConfigPanels() as $id => $configpanel) {
                $configpanel_block_tpl = new ilTemplate(
                    "tpl.startworkflow_configpanel_block.html",
                    true,
                    true,
                    $this->plugin->getDirectory()
                );
                $configpanel_block_tpl->setVariable('BLOCK_ID', $id);
                $configpanel_block_tpl->setVariable('CONFIGPANEL_BLOCK', $configpanel);
                $configpanel_blocks[] = $configpanel_block_tpl->get();
            }
            if (!empty($configpanel_blocks)) {
                $configpanel_section_tpl->setVariable('BLOCK_CONTENT', implode('', $configpanel_blocks));
                $tpl->setVariable('WORKFLOW_CONFIG_PANELS', $configpanel_section_tpl->get());
            }

            // Error messages.
            $tpl->setVariable(
                'NO_WORKFLOW_SELECTED_ERROR_TEXT',
                $this->plugin->txt('msg_startworkflow_no_workflow_seleced')
            );
            $tpl->setVariable(
                'CONFIG_PANEL_REQUIRED_ERROR_TEXT',
                $this->plugin->txt('msg_startworkflow_required_config_panel_item')
            );

            $submit_btn = $this->dic->ui()->factory()->button()->primary($this->dic->language()->txt("save"), '#')
                                    ->withOnLoadCode(function ($id) use ($form_submit_btn_id): string {
                                        return "$('#{$id}').click(function() { " .
                                            "$('#{$form_submit_btn_id}').click(); " .
                                            "return false; });";
                                    });

            $modal_startworkflow = $this->dic->ui()->factory()->modal()->roundtrip(
                $this->plugin->txt('event_startworkflow'),
                $this->dic->ui()->factory()->legacy($tpl->get())
            )->withActionButtons([$submit_btn]);
            $this->setStartworkflowModal($modal_startworkflow);
        }
    }

    /**
     * @throws ilTemplateException
     */
    public function initReportDate(): void
    {
        $this->setReportDateModal(
            $this->buildReportingModal(
                'reportDate',
                $this->plugin->txt('event_report_date_modification'),
                nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_DATE_TEXT))
            )
        );
    }

    /**
     * @throws ilTemplateException
     */
    public function initReportQuality(): void
    {
        $this->setReportQualityModal(
            $this->buildReportingModal(
                "reportQuality",
                $this->plugin->txt('event_report_quality_problem'),
                nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY_TEXT))
            )
        );
    }

    /**
     *
     * @throws ilTemplateException
     */
    protected function buildReportingModal(string $cmd, string $title, string $body): RoundTrip
    {
        $tpl = new ilTemplate("tpl.reporting_modal.html", true, true, $this->plugin->getDirectory());

        $form_id = uniqid('form', false);
        $tpl->setVariable('FORM_ID', $form_id);
        $tpl->setVariable('FORM_ACTION', $this->dic->ctrl()->getFormAction($this->parent_gui, $cmd));
        $tpl->setVariable('BODY', $body);

        $submit_btn = $this->dic->ui()->factory()->button()->primary($this->dic->language()->txt("send"), '#')
                                ->withOnLoadCode(function ($id) use ($form_id): string {
                                    return "$('#{$id}').click(function() { " .
                                        "$('#{$form_id}').submit(); " .
                                        "$(this).prop('disabled', true); " .
                                        "return false; });";
                                });

        return $this->dic->ui()->factory()->modal()->roundtrip(
            $title,
            $this->dic->ui()->factory()->legacy($tpl->get())
        )->withActionButtons([$submit_btn]);
    }

    /**
     * @return Component[]
     */
    public function getAllComponents(): array
    {
        $return = [];
        if (!is_null($this->report_date_modal)) {
            $return[] = $this->report_date_modal;
        }
        if (!is_null($this->report_quality_modal)) {
            $return[] = $this->report_quality_modal;
        }
        if (!is_null($this->startworkflow_modal)) {
            $return[] = $this->startworkflow_modal;
        }
        return $return;
    }

    /**
     * @return Modal|null
     */
    public function getReportQualityModal()
    {
        return $this->report_quality_modal;
    }

    public function setReportQualityModal(Modal $report_quality_modal): void
    {
        $this->report_quality_modal = $report_quality_modal;
    }

    /**
     * @return Modal|null
     */
    public function getReportDateModal()
    {
        return $this->report_date_modal;
    }

    public function setReportDateModal(Modal $report_date_modal): void
    {
        $this->report_date_modal = $report_date_modal;
    }

    /**
     * @return Modal|null
     */
    public function getStartworkflowModal()
    {
        return $this->startworkflow_modal;
    }

    public function setStartworkflowModal(Modal $startworkflow_modal): void
    {
        $this->startworkflow_modal = $startworkflow_modal;
    }
}
