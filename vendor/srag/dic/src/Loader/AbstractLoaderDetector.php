<?php

namespace srag\DIC\OpencastObject\Loader;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\ComponentRenderer;
use ILIAS\UI\Implementation\Render\Loader;
use ILIAS\UI\Implementation\Render\RendererFactory;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class AbstractLoaderDetector
 *
 * @package srag\DIC\OpencastObject\Loader
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
    public function getRendererFactoryFor(Component $component) : RendererFactory
    {
        return $this->loader->getRendererFactoryFor($component);
    }


    /**
     * @inheritDoc
     */
    public function getRendererFor(Component $component, array $contexts) : ComponentRenderer
    {
        return $this->loader->getRendererFor($component, $contexts);
    }
}
