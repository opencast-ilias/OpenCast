<?php

namespace srag\CustomInputGUIs\OpenCast\InputGUIWrapperUIInputComponent;

use ILIAS\UI\Component\Input\Field\Input as InputInterface;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Component\Input\Field\Renderer as InputRenderer;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Implementation\Render\Template;
use ILIAS\UI\Renderer as RendererInterface;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class Renderer
 *
 * @package srag\CustomInputGUIs\OpenCast\InputGUIWrapperUIInputComponent
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class Renderer extends InputRenderer
{

    use DICTrait;


    /**
     * @inheritDoc
     */
    protected function getComponentInterfaceName() : array
    {
        return [
            InputGUIWrapperUIInputComponent::class
        ];
    }


    /**
     * @inheritDoc
     */
    protected function renderNoneGroupInput(InputInterface $input, RendererInterface $default_renderer) : string
    {
        $input_tpl = $this->getTemplate("input.html", true, true);

        $html = $this->renderInputFieldWithContext($input_tpl, $input, null, null);

        return $html;
    }


    /**
     * @inheritDoc
     */
    protected function renderInputField(Template $tpl, Input $input, $id) : string
    {
        $tpl->setVariable("INPUT", self::output()->getHTML($input->getInput()));

        return self::output()->getHTML($tpl);
    }


    /**
     * @inheritDoc
     */
    public function registerResources(ResourceRegistry $registry)/*: void*/
    {
        parent::registerResources($registry);

        $dir = __DIR__;
        $dir = "./" . substr($dir, strpos($dir, "/Customizing/") + 1);

        $registry->register($dir . "/css/InputGUIWrapperUIInputComponent.css");
    }


    /**
     * @inheritDoc
     */
    protected function getTemplatePath(/*string*/ $name) : string
    {
        if ($name === "input.html") {
            return __DIR__ . "/templates/" . $name;
        } else {
            // return parent::getTemplatePath($name);
            return "src/UI/templates/default/Input/" . $name;
        }
    }
}
