<?php

namespace srag\DataTableUI\OpenCast\Component\Format;

use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Table;

/**
 * Interface Format
 *
 * @package srag\DataTableUI\OpenCast\Component\Format
 */
interface Format
{

    /**
     * @var string
     */
    const FORMAT_BROWSER = "browser";
    /**
     * @var string
     */
    const FORMAT_CSV = "csv";
    /**
     * @var string
     */
    const FORMAT_EXCEL = "excel";
    /**
     * @var string
     */
    const FORMAT_HTML = "html";
    /**
     * @var string
     */
    const FORMAT_PDF = "pdf";
    /**
     * @var int
     */
    const OUTPUT_TYPE_DOWNLOAD = 2;
    /**
     * @var int
     */
    const OUTPUT_TYPE_PRINT = 1;


    /**
     * @param string $data
     * @param Table  $component
     */
    public function deliverDownload(string $data, Table $component) : void;


    /**
     * @param Table $component
     *
     * @return string
     */
    public function getDisplayTitle(Table $component) : string;


    /**
     * @return string
     */
    public function getFormatId() : string;


    /**
     * @return int
     */
    public function getOutputType() : int;


    /**
     * @return object
     */
    public function getTemplate() : object;


    /**
     * @param Table     $component
     * @param Data|null $data
     * @param Settings  $settings
     *
     * @return string
     */
    public function render(Table $component, ?Data $data, Settings $settings) : string;
}
