<?php

declare(strict_types=1);

use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowRepository;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\LegacyHelpers\TableGUIConstants;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctWorkflowTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowTableGUI extends ilTable2GUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }
    use \srag\Plugins\Opencast\LegacyHelpers\TableGUI;

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? self::LANG_MODULE : $module, $fallback);
    }


    public const LANG_MODULE = 'workflow';
    protected Factory $factory;
    /**
     * @var Modal[]
     */
    protected array $modals = [];
    protected Renderer $renderer;
    private ilOpenCastPlugin $plugin;

    public function __construct(?object $parent, string $parent_cmd, protected WorkflowRepository $workflow_repository)
    {
        global $DIC;
        $opencastContainer = Init::init();
        $this->plugin = $opencastContainer->get(ilOpenCastPlugin::class);
        $ui = $DIC->ui();
        $this->factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        parent::__construct($parent, $parent_cmd);
        $this->initTable();
        $this->setDescription($this->getLocaleString('workflows_info', 'msg'));
        $parent->setTabParameter(xoctMainGUI::SUBTAB_WORKFLOWS_LIST);
    }

    protected function getRowTemplate(): string
    {
        return $this->plugin->getDirectory() . '/templates/default/tpl.table_row.html';
    }

    protected function initColumns(): void
    {
        foreach ($this->getSelectableColumns2() as $col) {
            $txt = $col['txt'];
            $id = $col['id'];
            $sort = '';
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
            $action = ($id === 'action');

            $this->addColumn($txt, $sort, $width, $action);
        }
    }

    public function getHTML(): string
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
     */
    protected function getColumnValue(string $column, /*array*/ array $row, int $format = TableGUIConstants::DEFAULT_FORMAT): string
    {
        $row = WorkflowAR::find($row['id']);

        switch ($column) {
            case 'id':
                return $row->getWorkflowId();
            case 'title':
                return $row->getTitle();
            case 'description':
                return $row->getDescription();
            case 'tags':
                return str_replace(',', '<br />', $row->getTags());
            case 'config_panel':
                $tpl = new ilTemplate("tpl.icon.html", true, true, $this->plugin->getDirectory());
                $has_config_panel = !empty($row->getConfigPanel());
                $icon = $has_config_panel ? 'checkbox_checked.png' : 'checkbox_unchecked.png';
                $tpl->setCurrentBlock('icon');
                $tpl->setVariable('ICON_SRC', ilUtil::getHtmlPath(ilUtil::getImagePath($icon)));
                $tpl->setVariable('ICON_ALT', $icon);
                $icon_title = $has_config_panel ?
                    $this->getLocaleString('config_panel_icon_with', self::LANG_MODULE) :
                    $this->getLocaleString('config_panel_icon_without', self::LANG_MODULE);
                $tpl->setVariable('ICON_TITLE', $icon_title);
                $tpl->parseCurrentBlock();
                return $tpl->get();
            case 'actions':
                $this->ctrl->setParameter($this->parent_obj, 'workflow_id', $row->getId());
                $delete_modal = $this->factory->modal()->interruptive(
                    $this->getLocaleString('delete', 'common'),
                    $this->txt('msg_confirm_delete_workflow'),
                    $this->ctrl->getFormAction($this->parent_obj, xoctGUI::CMD_DELETE)
                )->withAffectedItems(
                    [
                        $this->factory->modal()->interruptiveItem(
                            (string) $row->getId(),
                            $row->getTitle()
                        )
                    ]
                );
                $this->modals[] = $delete_modal;
                $actions = $this->factory->dropdown()->standard(
                    [
                        $this->factory->button()->shy(
                            $this->getLocaleString('edit', 'common'),
                            $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_EDIT)
                        ),
                        $this->factory->button()->shy(
                            $this->getLocaleString('delete', 'common'),
                            $delete_modal->getShowSignal()
                        )
                    ]
                )->withLabel($this->getLocaleString('actions', 'common'));
                return $this->renderer->render($actions);
        }

        return '';
    }

    protected function getSelectableColumns2(): array
    {
        return [
            ['txt' => $this->getLocaleString('id'), 'id' => 'id'],
            ['txt' => $this->getLocaleString('title'), 'id' => 'title'],
            ['txt' => $this->getLocaleString('description'), 'id' => 'description'],
            ['txt' => $this->getLocaleString('tags', self::LANG_MODULE), 'id' => 'tags'],
            ['txt' => $this->getLocaleString('config_panel', self::LANG_MODULE), 'id' => 'config_panel'],
            ['txt' => $this->getLocaleString('actions', 'common'), 'id' => 'actions']
        ];
    }

    public function isColumnSelected($col): bool
    {
        return true;
    }

    protected function initData(): void
    {
        $this->setData($this->workflow_repository->getAllWorkflows(true));
    }

    protected function initId(): void
    {
        $this->setId('xoct_workflows');
    }

    protected function initTitle(): void
    {
        $this->setTitle($this->getLocaleString('table_title', self::LANG_MODULE));
    }
}
