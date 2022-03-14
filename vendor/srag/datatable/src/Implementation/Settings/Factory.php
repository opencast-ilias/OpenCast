<?php

namespace srag\DataTableUI\OpenCast\Implementation\Settings;

use ILIAS\UI\Component\ViewControl\Pagination;
use srag\DataTableUI\OpenCast\Component\Settings\Factory as FactoryInterface;
use srag\DataTableUI\OpenCast\Component\Settings\Settings as SettingsInterface;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\Factory as SortFactoryInterface;
use srag\DataTableUI\OpenCast\Component\Settings\Storage\Factory as StorageFactoryInterface;
use srag\DataTableUI\OpenCast\Implementation\Settings\Sort\Factory as SortFactory;
use srag\DataTableUI\OpenCast\Implementation\Settings\Storage\Factory as StorageFactory;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Settings
 */
class Factory implements FactoryInterface
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * Factory constructor
     */
    private function __construct()
    {

    }


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @inheritDoc
     */
    public function settings(Pagination $pagination) : SettingsInterface
    {
        return new Settings($pagination);
    }


    /**
     * @inheritDoc
     */
    public function sort() : SortFactoryInterface
    {
        return SortFactory::getInstance();
    }


    /**
     * @inheritDoc
     */
    public function storage() : StorageFactoryInterface
    {
        return StorageFactory::getInstance();
    }
}
