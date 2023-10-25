<?php

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;

/**
 * Class xoctWorkflowTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowTableGUI extends TableGUI
{
    use TranslatorTrait;
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
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;

    public function __construct($parent, string $parent_cmd, WorkflowRepository $workflow_repository)
    {
        global $DIC, $opencastContainer;
        $this->plugin = $opencastContainer->get(ilOpenCastPlugin::class);
        $ui = $DIC->ui();
        $this->workflow_repository = $workflow_repository;
        $this->factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($parent, $parent_cmd);
        $this->setDescription($this->translate('msg_workflows_info'));
        $parent->setTabParameter(xoctMainGUI::SUBTAB_WORKFLOWS_LIST);
    }

    /**
     * @throws DICException
     */
    protected function initColumns(): void
    {
        foreach ($this->getSelectableColumns2() as $col) {
            $txt = $col['txt'];
            $id = $col['id'];
            $sort = false;
            $width = '';
            switch ($id) {
                case 'description':
                    $width = '250px';
                    break;
                case 'id':
                    $width = '200px';
                    break;
                case 'config_panel':
                    $width = '24px';
                    break;
            }
            $action = ($id == 'action');

            $this->addColumn($txt, $sort, $width, $action);
        }
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
            case 'description':
                return $row->getDescription();
            case 'tags':
                return str_replace(',', '<br />', $row->getTags());
            case 'roles':
                return str_replace(',', '<br />', $row->getRoles());
            case 'config_panel':
                $tpl = new ilTemplate("tpl.icon.html", true, true, $this->plugin->getDirectory());
                $has_config_panel = !empty($row->getConfigPanel()) ? true : false;
                $icon = $has_config_panel ? 'checkbox_checked.png' : 'checkbox_unchecked.png';
                $tpl->setCurrentBlock('icon');
                $tpl->setVariable('ICON_SRC', ilUtil::getHtmlPath(ilUtil::getImagePath($icon)));
                $tpl->setVariable('ICON_ALT', $icon);
                $icon_title = $has_config_panel ?
                    $this->translate('config_panel_icon_with', self::LANG_MODULE) :
                    $this->translate('config_panel_icon_without', self::LANG_MODULE);
                $tpl->setVariable('ICON_TITLE', $icon_title);
                $tpl->parseCurrentBlock();
                return $tpl->get();
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
            ['txt' => $this->lng->txt('description'), 'id' => 'description'],
            ['txt' => $this->translate('tags', self::LANG_MODULE), 'id' => 'tags'],
            ['txt' => $this->translate('roles', self::LANG_MODULE), 'id' => 'roles'],
            ['txt' => $this->translate('config_panel', self::LANG_MODULE), 'id' => 'config_panel'],
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
        $this->setId('xoct_workflows');
    }

    /**
     * @inheritDoc
     * @throws DICException
     */
    protected function initTitle(): void
    {
        $this->setTitle($this->translate('table_title', self::LANG_MODULE));
    }
}
