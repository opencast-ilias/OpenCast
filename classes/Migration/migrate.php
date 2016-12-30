<?php
chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/xoctScaMigration.php');
xoctScaMigration::initAndRun();