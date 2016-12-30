<?php
chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/class.xoctScaMigration.php');
$migration = new xoctScaMigration();
try {
	$migration->run();
} catch (ilException $e) {
	xoctMigrationLog::getInstance()->write($e->getMessage());
	xoctMigrationLog::getInstance()->write('***Migration failed***');
}