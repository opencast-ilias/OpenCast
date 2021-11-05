<?php

use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTableBuilder;

/**
 * @ilCtrl_IsCalledBy xoctSeriesMetadataConfigGUI : xoctMetadataConfigRouterGUI
 */
class xoctSeriesMetadataConfigGUI extends xoctMetadataConfigGUI
{
    public function __construct(MDFieldConfigSeriesRepository $repository, MDCatalogueFactory $md_catalogue_factory)
    {
        parent::__construct($repository, new MDConfigTableBuilder($this, $repository), $md_catalogue_factory);
    }

    protected function getMetadataCatalogue(): MDCatalogue
    {
        return $this->md_catalogue_factory->series();
    }

    protected function getTableTitle(): string
    {
        return self::plugin()->translate('md_table_series');
    }
}