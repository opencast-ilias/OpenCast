<?php
use \srag\CustomInputGUIs\OpencastObject\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;

/**
 * Class xoctWorkflowParameterFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

	const PROPERTY_TITLE = 'setTitle';

	const F_ID = 'id';
	const F_TITLE = 'title';
	const F_TYPE = 'type';
	const F_DEFAULT_VALUE_MEMBER = 'default_value_member';
	const F_DEFAULT_VALUE_ADMIN = 'default_value_admin';

	/**
	 * @var WorkflowParameter
	 */
	protected $xoctWorkflowParameter;
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;


	public function __construct($parent, WorkflowParameterRepository $workflowParameterRepository, $param_id = '') {
		$this->xoctWorkflowParameter = WorkflowParameter::findOrGetInstance($param_id);
        $this->workflowParameterRepository = $workflowParameterRepository;
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
	protected function initCommands() : void
    {
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_PARAMETER, self::dic()->language()->txt('save'));
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_CANCEL, self::dic()->language()->txt('cancel'));
	}


	/**
	 *
	 */
	protected function initFields() : void
    {
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
					WorkflowParameter::TYPE_CHECKBOX => 'Checkbox'
				]
			],
			self::F_DEFAULT_VALUE_MEMBER => [
				self::PROPERTY_TITLE => self::plugin()->translate(self::F_DEFAULT_VALUE_MEMBER),
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueMember(),
				self::PROPERTY_OPTIONS => $this->workflowParameterRepository->getSelectionOptions()
			],
			self::F_DEFAULT_VALUE_ADMIN => [
				self::PROPERTY_TITLE => self::plugin()->translate(self::F_DEFAULT_VALUE_ADMIN),
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_REQUIRED => true,
				self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueAdmin(),
				self::PROPERTY_OPTIONS => $this->workflowParameterRepository->getSelectionOptions()
			],

		];
	}


	/**
	 *
	 */
	protected function initId() : void
    {
	}


	/**
	 *
	 */
	protected function initTitle() : void
    {
		$this->setTitle(self::dic()->language()->txt('edit'));
	}


	/**
	 * @return bool
	 */
	public function storeForm() : bool {
		if (!$this->storeFormCheck()) {
			return false;
		}

		$this->workflowParameterRepository->createOrUpdate(
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
	protected function storeValue(string $key, $value) : void
    {
	}
}
