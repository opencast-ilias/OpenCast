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
     * @var string
     */
    private $title;

    /**
     * @param $parent xoctEventMetadataConfigGUI|xoctSeriesMetadataConfigGUI
     * @param MDFieldConfigRepository $repository
     */
    public function __construct(xoctGUI $parent, MDFieldConfigRepository $repository)
    {
        parent::__construct($parent);
        $this->repository = $repository;
    }

    public function withTitle(string $title) : self
    {
        $clone = clone $this;
        $clone->title = $title;
        return $clone;
    }


    /**
     * @inheritDoc
     */
    protected function buildTable(): Table
    {
        $columnFactory = self::dataTableUI()->column();
        return (new \srag\DataTableUI\OpenCast\Implementation\Table('md_config_table',
            self::dic()->ctrl()->getLinkTarget($this->parent),
            $this->title ?? '',
            [
                $columnFactory->column('field_id', self::plugin()->translate('md_field_id'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('title_de', self::plugin()->translate('md_title_de'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('title_en', self::plugin()->translate('md_title_en'))
                    ->withSelectable(false)->withSortable(false),
                $columnFactory->column('visible_for_permissions', self::plugin()->translate('md_visible_for_permissions'))
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
                self::dic()->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_EDIT),
                self::dic()->ctrl()->getLinkTarget($this->parent, xoctGUI::CMD_DELETE))
        ))->withPlugin(self::plugin());
    }
}