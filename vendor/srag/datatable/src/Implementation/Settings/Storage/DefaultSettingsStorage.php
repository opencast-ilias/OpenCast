<?php

namespace srag\DataTableUI\OpenCast\Implementation\Settings\Storage;

use ilTablePropertiesStorage;
use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\Items\Items;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\SortField;

/**
 * Class DefaultSettingsStorage
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Settings\Storage
 */
class DefaultSettingsStorage extends AbstractSettingsStorage
{

    /**
     * @var ilTablePropertiesStorage
     */
    protected $properties_storage;


    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        $this->properties_storage = new ilTablePropertiesStorage();
        $this->properties_storage->properties = array_reduce(self::VARS, function (array $properties, string $property) : array {
            $properties[$property] = ["storage" => "db"];

            return $properties;
        }, []);
    }


    /**
     * @inheritDoc
     */
    public function read(string $table_id, int $user_id) : Settings
    {
        $settings = self::dataTableUI()->settings()->settings(self::dic()->ui()->factory()->viewControl()->pagination());

        foreach (self::VARS as $property) {
            $value = json_decode($this->properties_storage->getProperty($table_id, $user_id, $property) ?? "", true);

            if (!empty($value)) {
                switch ($property) {
                    case self::VAR_SORT_FIELDS:
                        $settings = $settings->withSortFields(array_map(function (array $sort_field) : SortField {
                            return self::dataTableUI()->settings()->sort()->sortField($sort_field[self::VAR_SORT_FIELD], $sort_field[self::VAR_SORT_FIELD_DIRECTION]);
                        }, $value));
                        break;

                    default:
                        $settings = Items::setter($settings, $property, $value);
                        break;
                }
            }
        }

        return $settings;
    }


    /**
     * @inheritDoc
     */
    public function store(Settings $settings, string $table_id, int $user_id) : void
    {
        foreach (self::VARS as $property) {
            $value = Items::getter($settings, $property);

            $this->properties_storage->storeProperty($table_id, $user_id, $property, json_encode($value));
        }
    }
}
