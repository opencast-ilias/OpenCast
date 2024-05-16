<?php

declare(strict_types=1);

namespace srag\Plugins\OpenCast\UI\Component\Input\Field;

use ILIAS\DI\Container;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\Input\Field\ChunkedFileRenderer;
use ILIAS\UI\Implementation\Component\Input\Field\ChunkedFile;
use ILIAS\UI\Implementation\Render\ComponentRenderer;
use ILIAS\UI\Implementation\Render\RendererFactory;
use ILIAS\Data\Factory;

/**
 * Class Loader
 */
class Loader implements \ILIAS\UI\Implementation\Render\Loader
{
    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var \ilOpenCastPlugin
     */
    protected $plugin;

    public function __construct(Container $dic, \ilOpenCastPlugin $plugin)
    {
        $this->dic = $dic;
        $this->plugin = $plugin;
    }

    public function getRendererFor(Component $component, array $contexts): ComponentRenderer
    {
        if ($component instanceof ChunkedFile) {
            $renderer = new ChunkedFileRenderer(
                $this->dic['ui.factory'],
                $this->dic["ui.template_factory"],
                $this->dic["lng"],
                $this->dic["ui.javascript_binding"],
                $this->dic["refinery"],
                $this->dic["ui.pathresolver"] ?? null,
                new Factory()
            );
            $renderer->registerResources($this->dic["ui.resource_registry"]);
            $renderer->setPluginInstance($this->plugin);

            return $renderer;
        }

        return $this->dic['ui.component_renderer_loader']->getRendererFor($component, $contexts);
    }

    public function getRendererFactoryFor(Component $component): RendererFactory
    {
        return $this->dic['ui.component_renderer_loader']->getRendererFactoryFor($component);
    }
}
