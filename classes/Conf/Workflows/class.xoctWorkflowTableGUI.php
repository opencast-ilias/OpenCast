<?php

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;

/**
 * Class xoctWorkflowTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowTableGUI extends TableGUI
{

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    const LANG_MODULE = 'workflow';

    /**
     * @var WorkflowRepository
     */
    protected $workflow_repository;
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var Modal[]
     */
    protected $modals = [];
    /**
     * @var Renderer
     */
    protected $renderer;

    public function __construct($parent, $parent_cmd, WorkflowRepository $workflow_repository)
    {
        $this->workflow_repository = $workflow_repository;
        $this->factory = self::dic()->ui()->factory();
        $this->renderer = self::dic()->ui()->renderer();
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($parent, $parent_cmd);
        $this->setDescription(self::plugin()->translate('msg_workflows_info'));
    }


    /**
     * @throws DICException
     */
    protected function initColumns() : void
    {
        $this->addColumn(self::dic()->language()->txt('id'));
        $this->addColumn(self::dic()->language()->txt('title'));
        $this->addColumn(self::plugin()->translate('parameters'));
        $this->addColumn(self::dic()->language()->txt('actions'), '', '', true);
    }


    public function getHTML()
    {
        $html = parent::getHTML();
        foreach ($this->modals as $modal) {
            $html .= $this->renderer->render($modal);
        }
        return $html;
    }


    /**
     * @inheritDoc
     *
     * @param     $column
     * @param     $row WorkflowAR
     * @param int $format
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function getColumnValue(string $column, /*array*/ $row, int $format = self::DEFAULT_FORMAT) : string
    {
        switch ($column) {
            case 'id':
                return $row->getWorkflowId();
            case 'title':
                return $row->getTitle();
            case 'parameters':
                return $row->getParameters();
            case 'actions':
                self::dic()->ctrl()->setParameter($this->parent_obj, 'workflow_id', $row->getId());
                $delete_modal = $this->factory->modal()->interruptive(
                    self::dic()->language()->txt('delete'),
                    $this->txt('msg_confirm_delete_workflow'),
                    self::dic()->ctrl()->getFormAction($this->parent_obj, xoctWorkflowGUI::CMD_DELETE)
                )->withAffectedItems(
                    [
                        $this->factory->modal()->interruptiveItem(
                            $row->getId(),
                            $row->getTitle()
                        )
                    ]
                );
                $this->modals[] = $delete_modal;
                $actions = $this->factory->dropdown()->standard(
                    [
                        $this->factory->button()->shy(
                            self::dic()->language()->txt('edit'),
                            self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctWorkflowGUI::CMD_EDIT)
                        ),
                        $this->factory->button()->shy(
                            self::dic()->language()->txt('delete'),
                            $delete_modal->getShowSignal()
                        )
                    ]
                )->withLabel(self::dic()->language()->txt('actions'));
                return self::output()->getHTML($actions);
        }
    }

    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function getSelectableColumns2() : array
    {
        return [
            ['txt' => self::dic()->language()->txt('id'), 'id' => 'id'],
            ['txt' => self::dic()->language()->txt('title'), 'id' => 'title'],
            ['txt' => self::plugin()->translate('parameters'), 'id' => 'parameters'],
            ['txt' => self::dic()->language()->txt('actions'), 'id' => 'actions']
        ];
    }


    /**
     * @param string $col
     *
     * @return bool
     */
    public function isColumnSelected($col) : bool
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    protected function initData() : void
    {
        $this->setData($this->workflow_repository->getAllWorkflows());
    }


    /**
     * @inheritDoc
     */
    protected function initFilterFields() : void
    {
        // TODO: Implement initFilterFields() method.
    }


    /**
     * @inheritDoc
     */
    protected function initId() : void
    {
        // TODO: Implement initId() method.
    }


    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function initTitle() : void
    {
        $this->setTitle(self::plugin()->translate('table_title', self::LANG_MODULE));
    }
}