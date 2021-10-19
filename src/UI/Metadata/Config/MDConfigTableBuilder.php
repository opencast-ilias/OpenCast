<?php

namespace srag\Plugins\Opencast\UI\Metadata\Config;

use ilOpenCastPlugin;
use srag\DataTableUI\OpenCast\Component\Table;
use srag\DataTableUI\OpenCast\Implementation\Utils\AbstractTableBuilder;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use xoctEventMetadataConfigGUI;
use xoctGUI;
use xoctSeriesMetadataConfigGUI;

class MDConfigTableBuilder extends AbstractTableBuilder
{

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    /**
     * @var MDFieldConfigRepository
     */
    private $repository;

    /**
     * @param $parent xoctEventMetadataConfigGUI|xoctSeriesMetadataConfigGUI
     * @param MDFieldConfigRepository $repository
     */
    public function __construct(xoctGUI $parent, MDFieldConfigRepository $repository)
    {
        parent::__construct($parent);
        $this->repository = $repository;
    }


    /**
     * @inheritDoc
     */
    protected function buildTable(): Table
    {
        $columnFactory = self::dataTableUI()->column();
        return (new \srag\DataTableUI\OpenCast\Implementation\Table('md_config_table',
            self::dic()->ctrl()->getLinkTarget($this->parent),
            "",
            [
                $columnFactory->column('field_id', self::plugin()->translate('md_field_id'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('title', self::plugin()->translate('md_title'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('visible_for_roles', self::plugin()->translate('md_visible_for_roles'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('required', self::plugin()->translate('md_required'))
                    ->withFormatter($columnFactory->formatter()->check())
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('read_only', self::plugin()->translate('md_read_only'))
                    ->withFormatter($columnFactory->formatter()->check())
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('prefill', self::plugin()->translate('md_prefill'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('actions', self::plugin()->translate('md_actions'))
                    ->withFormatter($columnFactory->formatter()->actions()->actionsDropdown())
                    ->withSelectable(false)->withSortable(false)
            ],
            new MDConfigDataFetcher($this->repository,
                self::dic()->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_UPDATE),
                self::dic()->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_DELETE))
        ))->withPlugin(self::plugin());
    }
}