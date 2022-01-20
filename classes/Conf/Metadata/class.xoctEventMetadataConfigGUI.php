<?php

use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;

/**
 * @ilCtrl_IsCalledBy xoctEventMetadataConfigGUI : xoctMetadataConfigRouterGUI
 */
class xoctEventMetadataConfigGUI extends xoctMetadataConfigGUI
{

    protected function getMetadataCatalogue(): MDCatalogue
    {
        return $this->md_catalogue_factory->event();
    }

    protected function getTableTitle(): string
    {
        return $this->plugin->txt('md_table_events');
    }
}