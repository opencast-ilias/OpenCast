<?php

namespace srag\DataTableUI\OpencastObject\Component\Settings;

use ILIAS\UI\Component\ViewControl\Pagination;
use srag\DataTableUI\OpencastObject\Component\Settings\Sort\Factory as SortFactory;
use srag\DataTableUI\OpencastObject\Component\Settings\Storage\Factory as StorageFactory;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Settings
 */
interface Factory
{

    /**
     * @param Pagination $pagination
     *
     * @return Settings
     */
    public function settings(Pagination $pagination) : Settings;


    /**
     * @return SortFactory
     */
    public function sort() : SortFactory;


    /**
     * @return StorageFactory
     */
    public function storage() : StorageFactory;
}
