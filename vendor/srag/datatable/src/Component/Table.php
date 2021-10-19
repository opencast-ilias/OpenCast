<?php

namespace srag\DataTableUI\OpenCast\Component;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Input\Field\FilterInput;
use srag\DataTableUI\OpenCast\Component\Column\Column;
use srag\DataTableUI\OpenCast\Component\Data\Fetcher\DataFetcher;
use srag\DataTableUI\OpenCast\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpenCast\Component\Format\Format;
use srag\DataTableUI\OpenCast\Component\Settings\Storage\SettingsStorage;
use srag\DIC\OpenCast\Plugin\Pluginable;
use srag\DIC\OpenCast\Plugin\PluginInterface;

/**
 * Interface Table
 *
 * @package srag\DataTableUI\OpenCast\Component
 */
interface Table extends Component, Pluginable
{

    /**
     * @var string
     */
    const ACTION_GET_VAR = "row_id";
    /**
     * @var string
     */
    const LANG_MODULE = "datatableui";
    /**
     * @var string
     */
    const MULTIPLE_SELECT_POST_VAR = "selected_row_ids";


    /**
     * @return string
     */
    public function getActionUrl() : string;


    /**
     * @return BrowserFormat
     */
    public function getBrowserFormat() : BrowserFormat;


    /**
     * @return Column[]
     */
    public function getColumns() : array;


    /**
     * @return DataFetcher
     */
    public function getDataFetcher() : DataFetcher;


    /**
     * @return FilterInput[]
     */
    public function getFilterFields() : array;


    /**
     * @return Format[]
     */
    public function getFormats() : array;


    /**
     * @return string[]
     */
    public function getMultipleActions() : array;


    /**
     * @return SettingsStorage
     */
    public function getSettingsStorage() : SettingsStorage;


    /**
     * @return string
     */
    public function getTableId() : string;


    /**
     * @return string
     */
    public function getTitle() : string;


    /**
     * @param string $action_url
     *
     * @return self
     */
    public function withActionUrl(string $action_url) : self;


    /**
     * @param BrowserFormat $browser_format
     *
     * @return self
     */
    public function withBrowserFormat(BrowserFormat $browser_format) : self;


    /**
     * @param Column[] $columns
     *
     * @return self
     */
    public function withColumns(array $columns) : self;


    /**
     * @param DataFetcher $data_fetcher
     *
     * @return self
     */
    public function withFetchData(DataFetcher $data_fetcher) : self;


    /**
     * @param FilterInput[] $filter_fields
     *
     * @return self
     */
    public function withFilterFields(array $filter_fields) : self;


    /**
     * @param Format[] $formats
     *
     * @return self
     */
    public function withFormats(array $formats) : self;


    /**
     * @param string[] $multiple_actions
     *
     * @return self
     */
    public function withMultipleActions(array $multiple_actions) : self;


    /**
     * @param PluginInterface $plugin
     *
     * @return self
     */
    public function withPlugin(PluginInterface $plugin) : self;


    /**
     * @param SettingsStorage $settings_storage
     *
     * @return self
     */
    public function withSettingsStorage(SettingsStorage $settings_storage) : self;


    /**
     * @param string $table_id
     *
     * @return self
     */
    public function withTableId(string $table_id) : self;


    /**
     * @param string $title
     *
     * @return self
     */
    public function withTitle(string $title) : self;
}
