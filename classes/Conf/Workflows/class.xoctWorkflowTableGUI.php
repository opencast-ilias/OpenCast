<?php

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;

/**
 * Class xoctWorkflowTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowTableGUI extends TableGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const LANG_MODULE = 'workflow';

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

    public function __construct($parent, string $parent_cmd, WorkflowRepository $workflow_repository)
    {
        global $DIC;
        $ui = $DIC->ui();
        $this->workflow_repository = $workflow_repository;
        $this->factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($parent, $parent_cmd);
        $this->setDescription(self::plugin()->translate('msg_workflows_info'));
    }

    /**
     * @throws DICException
     */
    protected function initColumns(): void
    {
        $this->addColumn($this->lng->txt('id'));
        $this->addColumn($this->lng->txt('title'));
        $this->addColumn(self::plugin()->translate('parameters'));
        $this->addColumn($this->lng->txt('actions'), '', '', true);
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
     *
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function getColumnValue(string $column, /*array*/ $row, int $format = self::DEFAULT_FORMAT): string
    {
        switch ($column) {
            case 'id':
                return $row->getWorkflowId();
            case 'title':
                return $row->getTitle();
            case 'parameters':
                return $row->getParameters();
            case 'actions':
                $this->ctrl->setParameter($this->parent_obj, 'workflow_id', $row->getId());
                $delete_modal = $this->factory->modal()->interruptive(
                    $this->lng->txt('delete'),
                    $this->txt('msg_confirm_delete_workflow'),
                    $this->ctrl->getFormAction($this->parent_obj, xoctWorkflowGUI::CMD_DELETE)
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
                            $this->lng->txt('edit'),
                            $this->ctrl->getLinkTarget($this->parent_obj, xoctWorkflowGUI::CMD_EDIT)
                        ),
                        $this->factory->button()->shy(
                            $this->lng->txt('delete'),
                            $delete_modal->getShowSignal()
                        )
                    ]
                )->withLabel($this->lng->txt('actions'));
                return self::output()->getHTML($actions);
        }

        return '';
    }

    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function getSelectableColumns2(): array
    {
        return [
            ['txt' => $this->lng->txt('id'), 'id' => 'id'],
            ['txt' => $this->lng->txt('title'), 'id' => 'title'],
            ['txt' => self::plugin()->translate('parameters'), 'id' => 'parameters'],
            ['txt' => $this->lng->txt('actions'), 'id' => 'actions']
        ];
    }

    /**
     * @param string $col
     */
    public function isColumnSelected($col): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function initData(): void
    {
        $this->setData($this->workflow_repository->getAllWorkflows());
    }

    /**
     * @inheritDoc
     */
    protected function initFilterFields(): void
    {
        // TODO: Implement initFilterFields() method.
    }

    /**
     * @inheritDoc
     */
    protected function initId(): void
    {
        // TODO: Implement initId() method.
    }

    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function initTitle(): void
    {
        $this->setTitle(self::plugin()->translate('table_title', self::LANG_MODULE));
    }
}
