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
		self::dic()->toolbar()->addInputItem($fileinput);
		$button = ilSubmitButton::getInstance();
		$button->setCaption('Migration Starten', false);
		$button->setCommand('migrate');
		self::dic()->toolbar()->addButtonInstance($button);

		self::dic()->toolbar()->setFormAction(self::dic()->ctrl()->getFormAction($this, 'migrate'), true);
	}

	protected function migrate() {

		$migration = new xoctScaMigration(file_get_contents($_FILES['xoct_migration']['tmp_name']));
		try {
			$results = $migration->run();
		} catch (ilException $e) {
			xoctMigrationLog::getInstance()->write($e->getMessage());
			xoctMigrationLog::getInstance()->write('***Migration failed***');
			ilUtil::sendFailure("{$e->getMessage()}. Check Log for Details.", true);
			self::dic()->ctrl()->redirect($this);
		}
		ilUtil::sendSuccess("Migration succeeded: {$results['migrated']} objects migrated, {$results['skipped']} objects skipped. Check Log for Details.", true);
		self::dic()->ctrl()->redirect($this);
	}

	protected function add() {
	}


	protected function create() {
	}


	protected function edit() {
	}


	protected function update() {
	}


	protected function confirmDelete() {
	}


	protected function delete() {
	}
}