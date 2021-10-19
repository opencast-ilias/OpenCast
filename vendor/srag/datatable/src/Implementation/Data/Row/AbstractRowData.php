<?php

namespace srag\DataTableUI\OpenCast\Implementation\Data\Row;

use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class AbstractRowData
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Data\Row
 */
abstract class AbstractRowData implements RowData
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * @var object
     */
    protected $original_data;
    /**
     * @var string
     */
    protected $row_id = "";


    /**
     * AbstractRowData constructor
     *
     * @param string $row_id
     * @param object $original_data
     */
    public function __construct(string $row_id, object $original_data)
    {
        $this->row_id = $row_id;
        $this->original_data = $original_data;
    }


    /**
     * @inheritDoc
     */
    public function getOriginalData() : object
    {
        return $this->original_data;
    }


    /**
     * @inheritDoc
     */
    public function getRowId() : string
    {
        return $this->row_id;
    }


    /**
     * @inheritDoc
     */
    public function withOriginalData(object $original_data) : RowData
    {
        $clone = clone $this;

        $clone->original_data = $original_data;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withRowId(string $row_id) : RowData
    {
        $clone = clone $this;

        $clone->row_id = $row_id;

        return $clone;
    }
}
