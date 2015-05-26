<?php
/**
 * getChannelInfo
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
chdir(strstr($_SERVER['SCRIPT_FILENAME'], 'Customizing', true));
require_once('./include/inc.header.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctSeries.php');
$xoctSeries = xoctSeries::find($_GET['identifier']);
header('Content-type: application/json');
echo $xoctSeries->__toJson();