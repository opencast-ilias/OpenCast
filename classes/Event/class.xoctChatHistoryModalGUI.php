<?php
use srag\DIC\OpenCast\DICTrait;

/**
 * Class xoctChatHistoryModalGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctChatHistoryModalGUI extends ilModalGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var xoctEventGUI
	 */
	protected $parent_gui;

	/**
	 * xoctChatHistoryModalGUI constructor.
	 */
	public function __construct($parent_gui) {
		$this->parent_gui = $parent_gui;

		self::dic()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getDirectory() . '/src/Chat/chat.css');

		$this->setId('xoct_chat_history_modal');
		$this->setHeading(self::plugin()->translate('event_chat_history'));
	}


	/**
	 * @return ilModalGUI|void
	 * @throws ilException
	 */
	static function getInstance() {
		throw new ilException('Do not use this method, please use the constructor instead.');
	}


	/**
	 * @return string
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 * @throws ilTemplateException
	 */
	function getHTML() {
		// only the following two lines differ from the parent method
		$tpl = new ilTemplate("tpl.chat_history_modal.html", true, true, self::plugin()->getPluginObject()->getDirectory());

		$tpl->setVariable("HEADING", $this->getHeading());

		$tpl->setVariable("MOD_ID", $this->getId());
		$tpl->setVariable("BODY", $this->getBody());

		switch ($this->getType())
		{
			case self::TYPE_LARGE:
				$tpl->setVariable("CLASS", "modal-lg");
				break;

			case self::TYPE_SMALL:
				$tpl->setVariable("CLASS", "modal-sm");
				break;
		}

		return $tpl->get();
	}
}