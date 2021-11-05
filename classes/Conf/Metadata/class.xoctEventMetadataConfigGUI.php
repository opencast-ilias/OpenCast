<?php

use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTableBuilder;

/**
 * @ilCtrl_IsCalledBy xoctEventMetadataConfigGUI : xoctMetadataConfigRouterGUI
 */
class xoctEventMetadataConfigGUI extends xoctMetadataConfigGUI
{

    public function __construct(MDFieldConfigEventRepository $repository, MDCatalogueFactory $md_catalogue_factory)
    {
        parent::__construct($repository, new MDConfigTableBuilder($this, $repository), $md_catalogue_factory);
    }

    protected function getMetadataCatalogue() : MDCatalogue
    {
        return $this->md_catalogue_factory->event();
    }

    protected function getTableTitle(): string
    {
        return self::plugin()->translate('md_table_events');
    }
}