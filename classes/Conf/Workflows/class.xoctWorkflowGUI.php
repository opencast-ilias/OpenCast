<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\LegacyHelpers\OutputTrait;

/**
 * Class xoctWorkflowGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowGUI : xoctMainGUI
 */
class xoctWorkflowGUI extends xoctGUI
{
    use OutputTrait;

    public const LANG_MODULE = 'workflow';
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

    public function __construct(WorkflowRepository $workflow_repository)
    {
        global $DIC;
        parent::__construct();
        $ui = $DIC->ui();
        $this->toolbar = $DIC->toolbar();
        $this->language = $DIC->language();
        $this->http = $DIC->http();
        $this->workflow_repository = $workflow_repository;
        $this->factory = $ui->factory();
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function index()
    {
        $this->initToolbar();
        $table = new xoctWorkflowTableGUI($this, self::CMD_STANDARD, $this->workflow_repository);
        $this->output($table);
    }

    protected function initToolbar()
    {
        $add_button = $this->factory->button()->primary(
            $this->plugin->txt('config_button_add_workflow'),
            $this->ctrl->getLinkTarget($this, self::CMD_ADD)
        );
        $this->toolbar->addComponent($add_button);
    }

    /**
     * @param WorkflowAR $workflow
     *
     * @return Standard
     * @throws DICException
     */
    protected function getForm(WorkflowAR $workflow = null): Standard
    {
        $id = $this->factory->input()->field()->text($this->language->txt('id'))->withRequired(true);
        $title = $this->factory->input()->field()->text($this->language->txt('title'))->withRequired(true);
        $parameters = $this->factory->input()->field()->text($this->plugin->txt('parameters'))->withByline(
            $this->plugin->txt('parameters_info')
        );

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
                        'parameters' => is_null($workflow) ? $parameters : $parameters->withValue(
                            $workflow->getParameters()
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
            $this->workflow_repository->store($data[0]['id'], $data[0]['title'], $data[0]['parameters']);
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
            $this->workflow_repository->store($data[0]['id'], $data[0]['title'], $data[0]['parameters'], $id);
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
            ilUtil::sendSuccess($this->plugin->txt('msg_workflow_deleted', self::LANG_MODULE), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
    }
}
