<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Metadata\Config;

use ILIAS\DI\Container;
use ilPlugin;
use ilTable2GUI;
use xoctGUI;
use WaitOverlay;

/**
 * Table for Plugin config -> Metadata
 * Uses JS for Drag'n'Drop sortation
 */
class MDConfigTable extends ilTable2GUI
{
    /**
     * @var xoctGUI
     */
    private $parent;
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var \ilTemplate
     */
    private $main_tpl;

    public function __construct(
        xoctGUI $parent,
        string $title,
        Container $dic,
        ilPlugin $plugin,
        array $data
    ) {
        $this->parent = $parent;
        $this->plugin = $plugin;
        $this->dic = $dic;
        $this->main_tpl = $dic->ui()->mainTemplate();
        new WaitOverlay($this->dic->ui()->mainTemplate());
        $this->setId('xoct_md_config');
        $this->setDescription($this->plugin->txt('msg_md_config_info'));
        parent::__construct($parent);
        $this->setTitle($title);
        $this->setLimit(0);
        $this->setEnableNumInfo(false);
        $this->setShowRowsSelector(false);
        $this->setRowTemplate($this->plugin->getDirectory() . '/templates/default/tpl.md_config.html');

        $this->initJS();
        $this->initColumns();
        $this->setData($data);
    }

    private function initJS(): void
    {
        $this->main_tpl->addJavaScript(
            $this->plugin->getDirectory() . '/templates/default/sortable.js'
        );
        $base_link = $this->dic->ctrl()->getLinkTarget($this->parent, 'reorder', '', true);
        $this->main_tpl->addOnLoadCode("xoctSortable.init('" . $base_link . "');");
    }

    protected function initColumns(): void
    {
        $this->addColumn("", "", "10px", true);
        $this->addColumn($this->plugin->txt('md_field_id'));
        $this->addColumn($this->plugin->txt('md_title_de'));
        $this->addColumn($this->plugin->txt('md_title_en'));
        $this->addColumn($this->plugin->txt('md_visible_for_permissions'));
        $this->addColumn($this->plugin->txt('md_required'));
        $this->addColumn($this->plugin->txt('md_read_only'));
        $this->addColumn($this->plugin->txt('md_prefill'));
        $this->addColumn("", "", '30px', true);
    }

    #[\ReturnTypeWillChange]
    protected function fillRow(/*array*/ $a_set): void
    {
        $a_set['actions'] = $this->buildActions($a_set);
        $a_set['required'] = $a_set['required'] ? 'ok' : 'not_ok';
        $a_set['read_only'] = $a_set['read_only'] ? 'ok' : 'not_ok';
        parent::fillRow($a_set);
    }

    protected function buildActions($a_set): string
    {
        $this->dic->ctrl()->setParameter($this->parent_obj, 'field_id', $a_set['field_id']);
        $actions = [
            $this->dic->ui()->factory()->button()->shy(
                $this->dic->language()->txt('edit'),
                $this->dic->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_EDIT)
            )
        ];
        $delete_modal = $this->dic->ui()->factory()->modal()->interruptive(
            $this->plugin->txt('delete_modal_title'),
            $this->plugin->txt('msg_confirm_delete'),
            $this->dic->ctrl()->getFormAction($this->parent_obj, 'delete')
        )->withAffectedItems([
            $this->dic->ui()->factory()->modal()->interruptiveItem(
                $a_set['field_id'],
                $a_set['title_de']
            )
        ]);
        $actions[] = $this->dic->ui()->factory()->button()->shy(
            $this->dic->language()->txt('delete'),
            '#'
        )->withOnClick($delete_modal->getShowSignal());
        return $this->dic->ui()->renderer()->render(
            $this->dic->ui()->factory()->dropdown()->standard([
                    $actions
                ])->withLabel($this->dic->language()->txt('actions'))
        ) . (isset($delete_modal) ? $this->dic->ui()->renderer()->render($delete_modal) : '');
    }
}
