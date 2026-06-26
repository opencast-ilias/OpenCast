<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Metadata\Config;

use ILIAS\DI\Container;
use ilPlugin;
use ilTable2GUI;
use xoctGUI;
use WaitOverlay;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;

/**
 * Table for Plugin config -> Metadata
 * Uses JS for Drag'n'Drop sortation
 */
class MDConfigTable extends ilTable2GUI
{
    /**
     * @readonly
     */
    private Factory $ui_factory;

    public function __construct(
        private xoctGUI $parent,
        private MDCatalogue $catalogue,
        string $title,
        private Container $dic,
        private ilPlugin $plugin,
        array $data
    ) {
        $this->ui_factory = $this->dic->ui()->factory();
        new WaitOverlay($this->dic->ui()->mainTemplate());
        $this->setId('xoct_md_config');
        $this->setDescription($this->plugin->txt('msg_md_config_info'));
        parent::__construct($this->parent);
        $this->setTitle($title);
        $this->setLimit(0);
        $this->setEnableNumInfo(false);
        $this->setShowRowsSelector(false);
        $this->setRowTemplate(
            'tpl.md_config.html',
            'public/Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/'
        );

        $this->initJS();
        $this->initColumns();
        $this->setData($data);
    }

    private function initJS(): void
    {
        $this->main_tpl->addJavaScript(
            $this->plugin->getRelativeDirectory() . '/templates/default/sortable.js'
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
    protected function fillRow(/*array*/ array $a_set): void
    {
        $a_set['actions'] = $this->buildActions($a_set);
        $a_set['required'] = $this->boolIconSrc((bool) $a_set['required']);
        $a_set['read_only'] = $this->boolIconSrc((bool) $a_set['read_only']);
        parent::fillRow($a_set);
    }

    /**
     * Resolves the check-/cross-icon path via the ILIAS asset resolver instead
     * of a hard-coded template path (the standard images moved from
     * templates/default/images to assets/images in ILIAS 10).
     */
    private function boolIconSrc(bool $value): string
    {
        return \ilUtil::getHtmlPath(
            \ilUtil::getImagePath($value ? 'standard/icon_ok.svg' : 'standard/icon_not_ok.svg')
        );
    }

    protected function buildActions(array $a_set): string
    {
        $this->dic->ctrl()->setParameter($this->parent_obj, 'field_id', $a_set['field_id']);

        $definitions = $this->catalogue->getFieldById($a_set['field_id']);

        $actions = [
            $this->ui_factory
                ->button()
                ->shy(
                    $this->dic->language()->txt('edit'),
                    $this->dic->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_EDIT)
                )
        ];
        $delete_modal = $this->ui_factory
            ->modal()
            ->interruptive(
                $this->plugin->txt('delete_modal_title'),
                $this->plugin->txt('msg_confirm_delete'),
                $this->dic->ctrl()->getFormAction($this->parent_obj, 'delete')
            )
            ->withAffectedItems([
                $this->ui_factory->modal()->interruptiveItem()->keyValue(
                    $a_set['field_id'],
                    $a_set['field_id'],
                    $a_set['title_de']
                )
            ]);

        $delete_button = $this->ui_factory
            ->button()
            ->shy(
                $this->dic->language()->txt('delete'),
                '#'
            )
            ->withOnClick($delete_modal->getShowSignal());


        if ($definitions->isMandatory()) {
            $delete_button = $delete_button->withUnavailableAction();
        }

        $actions[] = $delete_button;

        return $this->dic->ui()
                         ->renderer()
                         ->render(
                             $this->ui_factory
                                 ->dropdown()
                                 ->standard(
                                     $actions
                                 )
                                 ->withLabel($this->dic->language()->txt('actions'))
                         ) . (isset($delete_modal) ? $this->dic->ui()->renderer()->render($delete_modal) : '');
    }
}
