<?php

declare(strict_types=1);

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;

/**
 * Class xoctWorkflowParametersFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParametersFormGUI extends ilPropertyFormGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'workflow_params' : $module, $fallback);
    }

    public const PROPERTY_TITLE = 'setTitle';
    public const PROPERTY_INFO = 'setInfo';

    public const F_OVERWRITE_SERIES_PARAMS = 'overwrite_series_params';

    protected \xoctWorkflowParameterGUI $parent;

    public function __construct(xoctWorkflowParameterGUI $parent)
    {
        $this->parent = $parent;
        parent::__construct();
        $this->initForm();
    }

    private function initForm(): void
    {
        $this->initAction();
        $this->initCommands();
        $this->initTitle();
        $this->initFields();
    }

    protected function initAction(): void
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent));
    }


    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_FORM, $this->lng->txt('save', 'common'));
        $this->addCommandButton(xoctGUI::CMD_CANCEL, $this->getLocaleString('cancel', 'common'));
    }

    protected function initFields(): void
    {
        $field = new ilCheckboxInputGUI($this->getLocaleString(
            PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES,
            'config'
        ), PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES);
        $field->setValue("1");
        $field->setChecked((bool) PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES));

        $sub_item = new ilCheckboxInputGUI($this->getLocaleString(self::F_OVERWRITE_SERIES_PARAMS, 'config'), self::F_OVERWRITE_SERIES_PARAMS);
        $sub_item->setInfo($this->getLocaleString(
            self::F_OVERWRITE_SERIES_PARAMS . '_info',
            'config'
        ));

        $field->addSubItem($sub_item);

        $this->addItem($field);
    }


    protected function initTitle(): void
    {
        $this->setTitle($this->getLocaleString('settings', 'tab'));
    }

    public function storeForm(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        PluginConfig::set(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES, $this->getInput(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES));

        return true;
    }
}
