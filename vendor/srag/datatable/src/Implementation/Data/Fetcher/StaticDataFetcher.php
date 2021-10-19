<?php

namespace srag\DataTableUI\OpenCast\Implementation\Data\Fetcher;

use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\SortField;
use stdClass;

/**
 * Class StaticDataFetcher
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Data\Fetcher
 */
class StaticDataFetcher extends AbstractDataFetcher
{

    /**
     * @var object[]
     */
    protected $data;
    /**
     * @var string
     */
    protected $id_key;


    /**
     * @inheritDoc
     *
     * @param object[] $data
     * @param string   $id_key
     */
    public function __construct(array $data, string $id_key)
    {
        parent::__construct();

        $this->data = $data;
        $this->id_key = $id_key;
    }


    /**
     * @inheritDoc
     */
    public function fetchData(Settings $settings) : Data
    {
        $data = array_filter($this->data, function (stdClass $data) use ($settings) : bool {
            $match = true;

            foreach ($settings->getFilterFieldValues() as $key => $value) {
                if (!empty($value)) {
                    switch (true) {
                        case is_array($value):
                            $match = in_array($data->{$key}, $value);
                            break;

                        case is_integer($data->{$key}):
                        case is_float($data->{$key}):
                            $match = ($data->{$key} === intval($value));
                            break;

                        case is_string($data->{$key}):
                            $match = (stripos($data->{$key}, $value) !== false);
                            break;

                        default:
                            $match = ($data->{$key} === $value);
                            break;
                    }

                    if (!$match) {
                        break;
                    }
                }
            }

            return $match;
        });

        usort($data, function (stdClass $o1, stdClass $o2) use ($settings) : int {
            foreach ($settings->getSortFields() as $sort_field) {
                $s1 = strval($o1->{$sort_field->getSortField()});
                $s2 = strval($o2->{$sort_field->getSortField()});

                $i = strnatcmp($s1, $s2);

                if ($sort_field->getSortFieldDirection() === SortField::SORT_DIRECTION_DOWN) {
                    $i *= -1;
                }

                if ($i !== 0) {
                    return $i;
                }
            }

            return 0;
        });

        $max_count = count($data);

        $data = array_slice($data, $settings->getOffset(), $settings->getRowsCount());

        $data = array_map(function (stdClass $row) : RowData {
            return self::dataTableUI()->data()->row()->property(strval($row->{$this->id_key}), $row);
        }, $data);

        return self::dataTableUI()->data()->data($data, $max_count);
    }
}
