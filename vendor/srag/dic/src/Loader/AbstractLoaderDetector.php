<?php

namespace srag\DIC\OpenCast\Loader;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\ComponentRenderer;
use ILIAS\UI\Implementation\Render\Loader;
use ILIAS\UI\Implementation\Render\RendererFactory;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class AbstractLoaderDetector
 *
 * @package srag\DIC\OpenCast\Loader
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
abstract class AbstractLoaderDetector implements Loader
{

    use DICTrait;

    /**
     * @var Loader
     */
    protected $loader;


    /**
     * AbstractLoaderDetector constructor
     *
     * @param Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }


    /**
     * @inheritDoc
     */
    public function getRendererFor(Component $component, array $contexts) : ComponentRenderer
    {
        return $this->loader->getRendererFor($component, $contexts);
    }


    /**
     * @inheritDoc
     */
    public function getRendererFactoryFor(Component $component) : RendererFactory
    {
        return $this->loader->getRendererFactoryFor($component);
    }
}
