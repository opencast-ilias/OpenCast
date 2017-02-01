<?php
if (count($_SERVER['argv']) < 5) {
	echo "ERROR: Arguments missing\n";
	echo "Usage: migrate.php [username] [password] [clientId] [mapping_data]\n";
	exit;
}
$mapping_data = file_get_contents($_SERVER['argv'][4]);
chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));

echo "initializing ILIAS ... ";
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoct.php';
xoct::initILIAS();
echo "OK\n";

require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/class.xoctScaMigration.php';
$migration = new xoctScaMigration($mapping_data, true);
try {
	$return = $migration->run();
} catch (Exception $e) {
	echo $e->getMessage();
}
//echo "Migrated: " . $return['migrated'] . "\n";
//echo "Skipped: " . $return['skipped'] . "\n";
