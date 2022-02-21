<?php

namespace srag\Plugins\Opencast\UI\Modal;

use ilHiddenInputGUI;
use ILIAS\DI\Container;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Modal\RoundTrip;
use ilOpenCastPlugin;
use ilPropertyFormGUI;
use ilSelectInputGUI;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;

/**
 * Class EventModals
 *
 * @package srag\Plugins\Opencast\UI\Modal
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
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
    protected $republish_modal;
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

    public function __construct($parent_gui, ilOpenCastPlugin $plugin, Container $dic, WorkflowRepository $workflow_repository)
    {
        $this->parent_gui = $parent_gui;
        $this->dic = $dic;
        $this->workflow_repository = $workflow_repository;
        $this->plugin = $plugin;

    }

    public function initRepublish()
    {
        if ($this->workflow_repository->anyWorkflowExists()) {
            $form = new ilPropertyFormGUI();
            $form->setFormAction($this->dic->ctrl()->getFormAction($this->parent_gui, "republish"));
            $form->setId(uniqid('form'));

            $select = new ilSelectInputGUI($this->plugin->txt('workflow'), 'workflow_id');
            $select->setOptions($this->workflow_repository->getAllWorkflowsAsArray('id', 'title'));
            $form->addItem($select);

            $hidden = new ilHiddenInputGUI('republish_event_id');
            $form->addItem($hidden);

            $form_id = 'form_' . $form->getId();
            $submit_btn = $this->dic->ui()->factory()->button()->primary($this->dic->language()->txt("save"), '#')
                ->withOnLoadCode(function ($id) use ($form_id) {
                    return "$('#{$id}').click(function() { " .
                        "$('#{$form_id}').submit(); " .
                        "$(this).prop('disabled', true); " .
                        "return false; });";
                });

            $modal_republish = $this->dic->ui()->factory()->modal()->roundtrip(
                $this->plugin->txt('event_republish'),
                $this->dic->ui()->factory()->legacy($form->getHTML())

            )->withActionButtons([$submit_btn]);
            $this->setRepublishModal($modal_republish);
        }
    }


    /**
     * @throws ilTemplateException
     */
    public function initReportDate()
    {
        $this->setReportDateModal($this->buildReportingModal(
            'reportDate',
            $this->plugin->txt('event_report_date_modification'),
            nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_DATE_TEXT))
        ));
    }


    /**
     * @throws ilTemplateException
     */
    public function initReportQuality()
    {
        $this->setReportQualityModal($this->buildReportingModal(
            "reportQuality",
            $this->plugin->txt('event_report_quality_problem'),
            nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY_TEXT))
        ));
    }


    /**
     * @param string $cmd
     * @param string $title
     * @param string $body
     *
     * @return RoundTrip
     * @throws ilTemplateException
     */
    protected function buildReportingModal(string $cmd, string $title, string $body) : RoundTrip
    {
        $tpl = new ilTemplate("tpl.reporting_modal.html", true, true, $this->plugin->getDirectory());

        $form_id = uniqid('form');
        $tpl->setVariable('FORM_ID', $form_id);
        $tpl->setVariable('FORM_ACTION', $this->dic->ctrl()->getFormAction($this->parent_gui, $cmd));
        $tpl->setVariable('BODY', $body);

        $submit_btn = $this->dic->ui()->factory()->button()->primary($this->dic->language()->txt("send"), '#')
            ->withOnLoadCode(function ($id) use ($form_id) {
                return "$('#{$id}').click(function() { " .
                    "$('#{$form_id}').submit(); " .
                    "$(this).prop('disabled', true); " .
                    "return false; });";
            });

        $modal = $this->dic->ui()->factory()->modal()->roundtrip(
            $title,
            $this->dic->ui()->factory()->legacy($tpl->get())

        )->withActionButtons([$submit_btn]);

        return $modal;
    }


    /**
     * @return Component[]
     */
    public function getAllComponents() : array
    {
        $return = [];
        if (!is_null($this->report_date_modal)) {
            $return[] = $this->report_date_modal;
        }
        if (!is_null($this->report_quality_modal)) {
            $return[] = $this->report_quality_modal;
        }
        if (!is_null($this->republish_modal)) {
            $return[] = $this->republish_modal;
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


    /**
     * @param Modal $report_quality_modal
     */
    public function setReportQualityModal(Modal $report_quality_modal)
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


    /**
     * @param Modal $report_date_modal
     */
    public function setReportDateModal(Modal $report_date_modal)
    {
        $this->report_date_modal = $report_date_modal;
    }


    /**
     * @return Modal|null
     */
    public function getRepublishModal()
    {
        return $this->republish_modal;
    }


    /**
     * @param Modal $republish_modal
     */
    public function setRepublishModal(Modal $republish_modal)
    {
        $this->republish_modal = $republish_modal;
    }
}