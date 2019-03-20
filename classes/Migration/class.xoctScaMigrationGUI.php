<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
/**
 * Class xoctScaMigrationGUI
 *
 * @ilCtrl_IsCalledBy xoctScaMigrationGUI : xoctMainGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctScaMigrationGUI extends xoctGUI{

	protected function index() {
		$fileinput = new ilFileInputGUI("", 'xoct_migration');
		$this->toolbar->addInputItem($fileinput);
		$button = ilSubmitButton::getInstance();
		$button->setCaption('Migration Starten', false);
		$button->setCommand('migrate');
		$this->toolbar->addButtonInstance($button);

		$this->toolbar->setFormAction($this->ctrl->getFormAction($this, 'migrate'), true);
	}

	protected function migrate() {

		$migration = new xoctScaMigration(file_get_contents($_FILES['xoct_migration']['tmp_name']));
		try {
			$results = $migration->run();
		} catch (ilException $e) {
			xoctMigrationLog::getInstance()->write($e->getMessage());
			xoctMigrationLog::getInstance()->write('***Migration failed***');
			ilUtil::sendFailure("{$e->getMessage()}. Check Log for Details.", true);
			$this->ctrl->redirect($this);
		}
		ilUtil::sendSuccess("Migration succeeded: {$results['migrated']} objects migrated, {$results['skipped']} objects skipped. Check Log for Details.", true);
		$this->ctrl->redirect($this);
	}

	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		// TODO: Implement create() method.
	}


	protected function edit() {
		// TODO: Implement edit() method.
	}


	protected function update() {
		// TODO: Implement update() method.
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}
}