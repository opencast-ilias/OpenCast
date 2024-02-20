<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;

/**
 * @ilCtrl_IsCalledBy xoctSeriesMetadataConfigGUI : xoctMetadataConfigRouterGUI
 */
class xoctSeriesMetadataConfigGUI extends xoctMetadataConfigGUI
{
    protected function getMetadataCatalogue(): MDCatalogue
    {
        return $this->md_catalogue_factory->series();
    }

    protected function getTableTitle(): string
    {
        return $this->plugin->txt('md_table_series');
    }
}
