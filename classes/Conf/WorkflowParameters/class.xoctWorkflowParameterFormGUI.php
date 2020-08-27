<?php
use \srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;

/**
 * Class xoctWorkflowParameterFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const PROPERTY_TITLE = 'setTitle';

	const F_ID = 'id';
	const F_TITLE = 'title';
	const F_TYPE = 'type';
	const F_DEFAULT_VALUE_MEMBER = 'default_value_member';
	const F_DEFAULT_VALUE_ADMIN = 'default_value_admin';

	/**
	 * @var xoctWorkflowParameter
	 */
	protected $xoctWorkflowParameter;


	/**
	 * xoctWorkflowParameterFormGUI constructor.
	 *
	 * @param $parent
	 */
	public function __construct($parent, $param_id = '') {
		$this->xoctWorkflowParameter = xoctWorkflowParameter::findOrGetInstance($param_id);
		parent::__construct($parent);
	}


	/**
	 * @param string $key
	 *
	 * @return mixed|void
	 */
	protected function getValue(string $key) {
	}


	/**
	 *
	 */
	protected function initCommands() {
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_PARAMETER, self::dic()->language()->txt('save'));
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_CANCEL, self::dic()->language()->txt('cancel'));
	}


	/**
	 *
	 */
	protected function initFields() {
		$this->fields = [
			self::F_ID => [
				self::PROPERTY_TITLE => self::dic()->language()->txt(self::F_ID),
				self::PROPERTY_CLASS => ilTextInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getId()
			],
			self::F_TITLE => [
				self::PROPERTY_TITLE => self::dic()->language()->txt(self::F_TITLE),
				self::PROPERTY_CLASS => ilTextInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getTitle()
			],
			self::F_TYPE => [
				self::PROPERTY_TITLE => self::dic()->language()->txt(self::F_TYPE),
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getType(),
				self::PROPERTY_OPTIONS => [
					xoctWorkflowParameter::TYPE_CHECKBOX => 'Checkbox'
				]
			],
			self::F_DEFAULT_VALUE_MEMBER => [
				self::PROPERTY_TITLE => self::plugin()->translate(self::F_DEFAULT_VALUE_MEMBER),
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueMember(),
				self::PROPERTY_OPTIONS => xoctWorkflowParameterRepository::getSelectionOptions()
			],
			self::F_DEFAULT_VALUE_ADMIN => [
				self::PROPERTY_TITLE => self::plugin()->translate(self::F_DEFAULT_VALUE_ADMIN),
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueAdmin(),
				self::PROPERTY_OPTIONS => xoctWorkflowParameterRepository::getSelectionOptions()
			],

		];
	}


	/**
	 *
	 */
	protected function initId() {
	}


	/**
	 *
	 */
	protected function initTitle() {
		$this->setTitle(self::dic()->language()->txt('edit'));
	}


	/**
	 * @return bool
	 */
	public function storeForm() : bool {
		if (!$this->storeFormCheck()) {
			return false;
		}

		xoctWorkflowParameterRepository::getInstance()->createOrUpdate(
			$this->getInput(self::F_ID),
			$this->getInput(self::F_TITLE),
			$this->getInput(self::F_TYPE),
			$this->getInput(self::F_DEFAULT_VALUE_MEMBER),
			$this->getInput(self::F_DEFAULT_VALUE_ADMIN)
		);

		return true;
	}


	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	protected function storeValue(string $key, $value) {
	}
}