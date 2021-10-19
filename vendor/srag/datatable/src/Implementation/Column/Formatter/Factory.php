<?php

namespace srag\DataTableUI\OpenCast\Implementation\Column\Formatter;

use srag\DataTableUI\OpenCast\Component\Column\Formatter\Actions\Factory as ActionsFactoryInterface;
use srag\DataTableUI\OpenCast\Component\Column\Formatter\Factory as FactoryInterface;
use srag\DataTableUI\OpenCast\Component\Column\Formatter\Formatter;
use srag\DataTableUI\OpenCast\Implementation\Column\Formatter\Actions\Factory as ActionsFactory;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Column\Formatter
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
    public function actions() : ActionsFactoryInterface
    {
        return ActionsFactory::getInstance();
    }


    /**
     * @inheritDoc
     */
    public function chainGetter(array $chain) : Formatter
    {
        return new ChainGetterFormatter($chain);
    }


    /**
     * @inheritDoc
     */
    public function check() : Formatter
    {
        return new CheckFormatter();
    }


    /**
     * @inheritDoc
     */
    public function date() : Formatter
    {
        return new DateFormatter();
    }


    /**
     * @inheritDoc
     */
    public function default() : Formatter
    {
        return new DefaultFormatter();
    }


    /**
     * @inheritDoc
     */
    public function image() : Formatter
    {
        return new ImageFormatter();
    }


    /**
     * @inheritDoc
     */
    public function languageVariable(string $prefix) : Formatter
    {
        return new LanguageVariableFormatter($prefix);
    }


    /**
     * @inheritDoc
     */
    public function learningProgress() : Formatter
    {
        return new LearningProgressFormatter();
    }


    /**
     * @inheritDoc
     */
    public function link() : Formatter
    {
        return new LinkFormatter();
    }


    /**
     * @inheritDoc
     */
    public function multiline() : Formatter
    {
        return new MultilineFormatter();
    }
}
