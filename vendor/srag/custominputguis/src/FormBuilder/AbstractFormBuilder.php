<?php

namespace srag\CustomInputGUIs\OpenCast\FormBuilder;

use ilFormPropertyDispatchGUI;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ilSubmitButton;
use srag\CustomInputGUIs\OpenCast\InputGUIWrapperUIInputComponent\InputGUIWrapperUIInputComponent;
use srag\DIC\OpenCast\DICTrait;
use Throwable;

/**
 * Class AbstractFormBuilder
 *
 * @package      srag\CustomInputGUIs\OpenCast\FormBuilder
 *
 * @author       studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @ilCtrl_Calls srag\CustomInputGUIs\OpenCast\FormBuilder\AbstractFormBuilder: ilFormPropertyDispatchGUI
 */
abstract class AbstractFormBuilder
{

    use DICTrait;
    /**
     * @var object
     */
    protected $parent;
    /**
     * @var Form|null
     */
    protected $form = null;


    /**
     * AbstractFormBuilder constructor
     *
     * @param object $parent
     */
    public function __construct(object $parent)
    {
        $this->parent = $parent;
    }


    /**
     * @return Form
     */
    protected function buildForm() : Form
    {
        $form = self::dic()->ui()->factory()->input()->container()->form()->standard(self::dic()->ctrl()->getFormAction($this->parent), [
            "form" => self::dic()->ui()->factory()->input()->field()->section($this->getFields(), $this->getTitle())
        ]);

        $data = $this->getData();

        foreach ($form->getInputs()["form"]->getInputs() as $key => &$field) {
            if (isset($data[$key])) {
                try {
                    $field = $field->withValue($data[$key]);
                } catch (Throwable $ex) {

                }
            }
        }

        return $form;
    }


    /**
     *
     */
    public function executeCommand() : void
    {
        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            case strtolower(ilFormPropertyDispatchGUI::class):
                foreach ($this->getForm()->getInputs()["form"]->getInputs() as $input) {
                    if ($input instanceof InputGUIWrapperUIInputComponent) {
                        if ($input->getInput()->getPostVar() === strval(filter_input(INPUT_GET, "postvar"))) {
                            $form_dispatcher = new ilFormPropertyDispatchGUI();
                            $form_dispatcher->setItem($input->getInput());
                            self::dic()->ctrl()->forwardCommand($form_dispatcher);
                            break;
                        }
                    }
                }
                break;

            default:
                break;
        }
    }


    /**
     * @return array
     */
    protected abstract function getButtons() : array;


    /**
     * @return array
     */
    protected abstract function getData() : array;


    /**
     * @return array
     */
    protected abstract function getFields() : array;


    /**
     * @return Form
     */
    public function getForm() : Form
    {
        if ($this->form === null) {
            $this->form = $this->buildForm();
        }

        return $this->form;
    }


    /**
     * @return string
     */
    protected abstract function getTitle() : string;


    /**
     * @return string
     */
    public function render() : string
    {
        $html = self::output()->getHTML(['<div class="AbstractFormBuilder">', $this->getForm(), '</div>']);

        $html = preg_replace_callback('/(<button\s+class\s*=\s*"btn btn-default"\s+data-action\s*=\s*"#"\s+id\s*=\s*"[a-z0-9_]+"\s*>)(.+)(<\/button\s*>)/',
            function (array $matches) : string {
                $buttons = [];

                foreach ($this->getButtons() as $cmd => $label) {
                    if (!empty($buttons)) {
                        $buttons[] = "&nbsp;";
                    }

                    $button = ilSubmitButton::getInstance();

                    $button->setCommand($cmd);

                    $button->setCaption($label, false);

                    $buttons[] = $button;
                }

                return self::output()->getHTML($buttons);
            }, $html);

        return $html;
    }


    /**
     * @return bool
     */
    public function storeForm() : bool
    {
        try {
            $this->form = $this->getForm()->withRequest(self::dic()->http()->request());

            $data = $this->form->getData();

            if (empty($data)) {
                return false;
            }

            $this->storeData($data["form"] ?? []);
        } catch (Throwable $ex) {
            return false;
        }

        return true;
    }


    /**
     * @param array $data
     */
    protected abstract function storeData(array $data) : void;
}
