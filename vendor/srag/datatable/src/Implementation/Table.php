<?php

namespace srag\DataTableUI\OpencastObject\Implementation;

use ILIAS\UI\Component\Input\Field\FilterInput;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Fetcher\DataFetcher;
use srag\DataTableUI\OpencastObject\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpencastObject\Component\Format\Format;
use srag\DataTableUI\OpencastObject\Component\Settings\Storage\SettingsStorage;
use srag\DataTableUI\OpencastObject\Component\Table as TableInterface;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;
use srag\DIC\OpencastObject\Plugin\PluginInterface;

/**
 * Class Table
 *
 * @package srag\DataTableUI\OpencastObject\Implementation
 */
class Table implements TableInterface
{

    use ComponentHelper;
    use DICTrait;
    use DataTableUITrait;

    /**
     * @var string
     */
    protected $action_url = "";
    /**
     * @var BrowserFormat
     */
    protected $browser_format;
    /**
     * @var Column[]
     */
    protected $columns = [];
    /**
     * @var DataFetcher
     */
    protected $data_fetcher;
    /**
     * @var FilterInput[]
     */
    protected $filter_fields = [];
    /**
     * @var Format[]
     */
    protected $formats = [];
    /**
     * @var string[]
     */
    protected $multiple_actions = [];
    /**
     * @var PluginInterface
     */
    protected $plugin;
    /**
     * @var SettingsStorage
     */
    protected $settings_storage;
    /**
     * @var string
     */
    protected $table_id = "";
    /**
     * @var string
     */
    protected $title = "";


    /**
     * Table constructor
     *
     * @param string      $table_id
     * @param string      $action_url
     * @param string      $title
     * @param Column[]    $columns
     * @param DataFetcher $data_fetcher
     */
    public function __construct(string $table_id, string $action_url, string $title, array $columns, DataFetcher $data_fetcher)
    {
        $this->table_id = $table_id;

        $this->action_url = $action_url;

        $this->title = $title;

        $classes = [Column::class];
        $this->checkArgListElements("columns", $columns, $classes);
        $this->columns = $columns;

        $this->data_fetcher = $data_fetcher;
    }


    /**
     * @inheritDoc
     */
    public function getActionUrl() : string
    {
        return $this->action_url;
    }


    /**
     * @inheritDoc
     */
    public function getBrowserFormat() : BrowserFormat
    {
        if ($this->browser_format === null) {
            $this->browser_format = self::dataTableUI()->format()->browser()->default();
        }

        return $this->browser_format;
    }


    /**
     * @inheritDoc
     */
    public function getColumns() : array
    {
        return $this->columns;
    }


    /**
     * @inheritDoc
     */
    public function getDataFetcher() : DataFetcher
    {
        return $this->data_fetcher;
    }


    /**
     * @inheritDoc
     */
    public function getFilterFields() : array
    {
        return $this->filter_fields;
    }


    /**
     * @inheritDoc
     */
    public function getFormats() : array
    {
        return $this->formats;
    }


    /**
     * @inheritDoc
     */
    public function getMultipleActions() : array
    {
        return $this->multiple_actions;
    }


    /**
     * @inheritDoc
     */
    public function getPlugin() : PluginInterface
    {
        return $this->plugin;
    }


    /**
     * @inheritDoc
     */
    public function getSettingsStorage() : SettingsStorage
    {
        if ($this->settings_storage === null) {
            $this->settings_storage = self::dataTableUI()->settings()->storage()->default();
        }

        return $this->settings_storage;
    }


    /**
     * @inheritDoc
     */
    public function getTableId() : string
    {
        return $this->table_id;
    }


    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return $this->title;
    }


    /**
     * @inheritDoc
     */
    public function withActionUrl(string $action_url) : TableInterface
    {
        $clone = clone $this;

        $clone->action_url = $action_url;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withBrowserFormat(BrowserFormat $browser_format) : TableInterface
    {
        $clone = clone $this;

        $clone->browser_format = $browser_format;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withColumns(array $columns) : TableInterface
    {
        $classes = [Column::class];
        $this->checkArgListElements("columns", $columns, $classes);

        $clone = clone $this;

        $clone->columns = $columns;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFetchData(DataFetcher $data_fetcher) : TableInterface
    {
        $clone = clone $this;

        $clone->data_fetcher = $data_fetcher;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFilterFields(array $filter_fields) : TableInterface
    {
        $classes = [FilterInput::class];
        $this->checkArgListElements("filter_fields", $filter_fields, $classes);

        $clone = clone $this;

        $clone->filter_fields = $filter_fields;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFormats(array $formats) : TableInterface
    {
        $classes = [Format::class];
        $this->checkArgListElements("formats", $formats, $classes);

        $clone = clone $this;

        $clone->formats = $formats;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withMultipleActions(array $multiple_actions) : TableInterface
    {
        $clone = clone $this;

        $clone->multiple_actions = $multiple_actions;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withPlugin(PluginInterface $plugin) : TableInterface
    {
        $clone = clone $this;

        $clone->plugin = $plugin;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSettingsStorage(SettingsStorage $settings_storage) : TableInterface
    {
        $clone = clone $this;

        $clone->settings_storage = $settings_storage;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withTableId(string $table_id) : TableInterface
    {
        $clone = clone $this;

        $clone->table_id = $table_id;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withTitle(string $title) : TableInterface
    {
        $clone = clone $this;

        $clone->title = $title;

        return $clone;
    }
}
