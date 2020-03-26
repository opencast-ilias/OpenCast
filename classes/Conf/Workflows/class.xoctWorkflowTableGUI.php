<?php

use ILIAS\UI\Factory;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Config\Workflow\Workflow;
use srag\Plugins\Opencast\Model\Config\Workflow\WorkflowRepository;

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
     * xoctWorkflowTableGUI constructor.
     *
     * @param $parent
     * @param $parent_cmd
     */
    public function __construct($parent, $parent_cmd)
    {
        $this->workflow_repository = new WorkflowRepository();
        $this->factory = self::dic()->ui()->factory();
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($parent, $parent_cmd);
    }


    /**
     * @throws DICException
     */
    protected function initColumns()
    {
        $this->addColumn(self::dic()->language()->txt('id'));
        $this->addColumn(self::dic()->language()->txt('title'));
        $this->addColumn(self::dic()->language()->txt('actions'), '', '', true);
    }


    /**
     * @inheritDoc
     *
     * @param     $column
     * @param     $row Workflow
     * @param int $format
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function getColumnValue($column, $row, $format = self::DEFAULT_FORMAT)
    {
        switch ($column) {
            case 'id':
                return $row->getWorkflowId();
            case 'title':
                return $row->getTitle();
            case 'actions':
                self::dic()->ctrl()->setParameter($this->parent_obj, 'workflow_id', $row->getId());
                $actions = $this->factory->dropdown()->standard(
                    [
                        $this->factory->button()->shy(
                            self::dic()->language()->txt('edit'),
                            self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctWorkflowGUI::CMD_EDIT)
                        ),
                        $this->factory->button()->shy(
                            self::dic()->language()->txt('delete'),
                            self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctWorkflowGUI::CMD_CONFIRM)
                        )
                    ]
                )->withLabel(self::dic()->language()->txt('actions'));
                return self::output()->getHTML($actions);
        }
    }


    /**
     * @inheritDoc
     */
    protected function getSelectableColumns2()
    {
        return [
            ['txt' => self::dic()->language()->txt('id'), 'id' => 'id'],
            ['txt' => self::dic()->language()->txt('title'), 'id' => 'title'],
            ['txt' => self::dic()->language()->txt('actions'), 'id' => 'actions']
        ];
    }


    /**
     * @param string $col
     *
     * @return bool
     */
    public function isColumnSelected($col)
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    protected function initData()
    {
        $this->setData($this->workflow_repository->getAllWorkflows());
    }


    /**
     * @inheritDoc
     */
    protected function initFilterFields()
    {
        // TODO: Implement initFilterFields() method.
    }


    /**
     * @inheritDoc
     */
    protected function initId()
    {
        // TODO: Implement initId() method.
    }


    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function initTitle()
    {
        $this->setTitle(self::plugin()->translate('table_title', self::LANG_MODULE));
    }
}