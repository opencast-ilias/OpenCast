<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctEventTableGUI.php');
require_once('class.xoctEventFormGUI.php');

/**
 * Class xoctEventGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctEventGUI: ilObjOpenCastGUI
 */
class xoctEventGUI extends xoctGUI {

	const IDENTIFIER = 'eid';


	/**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		parent::__construct();
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast();
		}
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_EVENTS);
		$this->tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/xoct.css');
	}


	protected function index() {
		if (ilObjOpenCastAccess::hasWriteAccess()) {
			$b = ilLinkButton::getInstance();
			$b->setCaption('rep_robj_xoct_event_add_new');
			$b->setUrl($this->ctrl->getLinkTarget($this, xoctEventGUI::CMD_ADD));
			$b->setPrimary(true);
			$this->toolbar->addButtonInstance($b);
		}
		$xoctEventTableGUI = new xoctEventTableGUI($this, self::CMD_STANDARD, $this->xoctOpenCast);
		$this->tpl->setContent($xoctEventTableGUI->getHTML());
	}


	protected function add() {
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);
		$this->tpl->setContent($xoctEventFormGUI->getHTML());
	}


	protected function create() {
		$xoctEventFormGUI = new xoctEventFormGUI($this, new xoctEvent(), $this->xoctOpenCast);
		$xoctEventFormGUI->setValuesByPost();
		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$this->tpl->setContent($xoctEventFormGUI->getHTML());
	}


	protected function edit() {
		$xoctEventFormGUI = new xoctEventFormGUI($this, xoctEvent::find($_GET[self::IDENTIFIER]), $this->xoctOpenCast);
		$xoctEventFormGUI->fillForm();
		$this->tpl->setContent($xoctEventFormGUI->getHTML());
	}


	protected function update() {
		$xoctEventFormGUI = new xoctEventFormGUI($this, xoctEvent::find($_GET[self::IDENTIFIER]), $this->xoctOpenCast);
		$xoctEventFormGUI->setValuesByPost();
		if ($xoctEventFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$this->tpl->setContent($xoctEventFormGUI->getHTML());
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}


	protected function view() {
		$event = xoctEvent::find($_GET[self::IDENTIFIER]);
		$this->tpl->setContent('<pre>' . print_r($event->__toArray(), true) . '</pre>');
	}


	protected function search() {
		/**
		 * @var $event xoctEvent
		 */
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->addCommandButton('import', 'Import');
		$self = new ilSelectInputGUI('import_identifier', 'import_identifier');

		$request = xoctRequest::root()->events()->parameter('limit', 1000);
		$data = json_decode($request->get());
		$ids = array();
		foreach ($data as $d) {
			$event = xoctEvent::find($d->identifier);
			$ids[$event->getIdentifier()] = $event->getTitle() . ' ( ' . $event->getIdentifier() . ' )';
		}
		array_multisort($ids);

		$self->setOptions($ids);
		$form->addItem($self);
		$this->tpl->setContent($form->getHTML());
	}


	protected function import() {
		/**
		 * @var $event xoctEvent
		 */
		$event = xoctEvent::find($_POST['import_identifier']);
		$event->setSeriesIdentifier($this->xoctOpenCast->getSeriesIdentifier());
		$event->updateSeries();
		$event->update();

		$this->ctrl->redirect($this, self::CMD_STANDARD);
	}


	protected function listAll() {
		/**
		 * @var $event xoctEvent
		 */
		$request = xoctRequest::root()->events()->parameter('limit', 1000);
		$content = '';
		foreach (json_decode($request->get()) as $d) {
			$event = xoctEvent::find($d->identifier);
			$content .= '<pre>' . print_r($event->__toStdClass(), 1) . '</pre>';
		}
		$this->tpl->setContent($content);
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function txt($key) {
		return $this->pl->txt('event_' . $key);
	}
}