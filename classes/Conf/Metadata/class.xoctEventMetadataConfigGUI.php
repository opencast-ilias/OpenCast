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

    public function __construct()
    {
        $repository = new MDFieldConfigEventRepository();
        parent::__construct($repository, new MDConfigTableBuilder($this, $repository));
    }

    protected function getMetadataCatalogue() : MDCatalogue
    {
        return MDCatalogueFactory::event();
    }

    protected function getTableTitle(): string
    {
        return self::plugin()->translate('md_table_events');
    }
}