<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Config\Workflow\Workflow;
use srag\Plugins\Opencast\Model\Config\Workflow\WorkflowRepository;

/**
 * Class xoctWorkflowGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowGUI : xoctMainGUI
 */
class xoctWorkflowGUI extends xoctGUI
{

    const LANG_MODULE = 'workflow';
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var WorkflowRepository
     */
    protected $workflow_repository;

    /**
     * xoctWorkflowGUI constructor.
     */
    public function __construct()
    {
        $this->workflow_repository = new WorkflowRepository();
        $this->factory = self::dic()->ui()->factory();
    }


    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function index()
    {
        ilUtil::sendInfo(self::plugin()->translate('msg_workflows_info'));
        $this->initToolbar();
        $table = new xoctWorkflowTableGUI($this, self::CMD_STANDARD);
        self::output()->output($table);
    }


    protected function initToolbar()
    {
        $add_button = $this->factory->button()->primary(
            self::plugin()->translate('config_button_add_workflow'),
            self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD)
        );
        self::dic()->toolbar()->addComponent($add_button);
    }


    /**
     * @param Workflow $workflow
     *
     * @return Standard
     * @throws DICException
     */
    protected function getForm(Workflow $workflow = null) : Standard
    {
        $id = $this->factory->input()->field()->text(self::dic()->language()->txt('id'))->withRequired(true);
        $title = $this->factory->input()->field()->text(self::dic()->language()->txt('title'))->withRequired(true);
        if (!is_null($workflow)) {
            self::dic()->ctrl()->setParameter($this, 'workflow_id', $workflow->getId());
        }
        return $this->factory->input()->container()->form()->standard(
            self::dic()->ctrl()->getFormAction($this, self::CMD_CREATE),
            [
                $this->factory->input()->field()->section(
                    [
                        'id'    => is_null($workflow) ? $id : $id->withValue($workflow->getId()),
                        'title' => is_null($workflow) ? $title : $title->withValue($workflow->getTitle())
                    ], self::plugin()->translate('workflows')
                )
            ]
        );
    }

    /**
     *
     */
    protected function add()
    {
        self::output()->output($this->getForm());
    }


    /**
     *
     */
    protected function create()
    {
        $form = $this->getForm()->withRequest(self::dic()->http()->request());
        if ($data = $form->getData()) {
            $this->workflow_repository->store($data[0]['id'], $data[0]['title']);
            ilUtil::sendSuccess(self::plugin()->translate('msg_workflow_created'), true);
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        } else {
            self::output()->output($form);
        }
    }


    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function edit()
    {
        $workflow_id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        self::output()->output($this->getForm(Workflow::find($workflow_id)));
    }


    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function update()
    {
        $workflow_id = filter_input(INPUT_GET, 'workflow_id', FILTER_SANITIZE_STRING);
        $form = $this->getForm(Workflow::find($workflow_id))->withRequest(self::dic()->http()->request());
        if ($data = $form->getData()) {
            $this->workflow_repository->store($data[0]['id'], $data[0]['title'], $workflow_id);
            ilUtil::sendSuccess(self::plugin()->translate('msg_workflow_updated'), true);
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        } else {
            self::output()->output($form);
        }
    }


    /**
     *
     */
    protected function confirmDelete()
    {
        // TODO: Implement confirmDelete() method.
    }


    /**
     *
     */
    protected function delete()
    {
        // TODO: Implement delete() method.
    }
}