<#1>
<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Cache\Service\DB\DBCacheAR;

\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
xoctConf::updateDB();
xoctIVTGroup::updateDB();
xoctOpenCast::updateDB();
?>
<#2>
<?php
xoctIVTGroupParticipant::updateDB();
xoctInvitation::updateDB();
?>
<#3>
<?php
xoctEventAdditions::updateDB();
?>
<#4>
<?php
require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");
$xoct_type_id = ilDBUpdateNewObjectType::addNewType(ilOpenCastPlugin::PLUGIN_ID, 'Plugin OpenCast');

//Adding a new Permission rep_robj_xoct_upload ("Upload")
$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
	'rep_robj_xoct_perm_upload', 'upload', 'object', 2010);
if($offering_admin)
{
	ilDBUpdateNewObjectType::addRBACOperation($xoct_type_id, $offering_admin);
}

//Adding a new Permission rep_robj_xoct_edit_videos ("Edit Videos")
$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
	'rep_robj_xoct_perm_edit_videos', 'edit videos', 'object', 2020);
if($offering_admin)
{
	ilDBUpdateNewObjectType::addRBACOperation($xoct_type_id, $offering_admin);
}
?>
<#5>
<?php
xoctOpenCast::updateDB();
?>
<#6>
<?php
//if (!xoctConf::getConfig(xoctConf::F_STD_ROLES)) {
//	$std_roles = array();
//	$std_roles[] = xoctConf::getConfig('role_ext_application');
//	$std_roles[] = xoctConf::getConfig('role_producer');
//	$std_roles = array_filter($std_roles);
//	if (!empty($std_roles)) {
//		xoctConf::set(xoctConf::F_STD_ROLES, $std_roles);
//	}
//}
?>
<#7>
<?php
//xoctConf::set(xoctConf::F_SIGN_PLAYER_LINKS, 1);
//xoctConf::set(xoctConf::F_SIGN_DOWNLOAD_LINKS, 1);
//xoctConf::set(xoctConf::F_SIGN_THUMBNAIL_LINKS, 1);
?>
<#8>
<?php
xoctPermissionTemplate::updateDB();
?>
<#9>
<?php
//xoctConf::set(xoctConf::F_REPORT_QUALITY_TEXT,
//	'Haben Sie Qualitätsprobleme mit dem Bild oder Ton Ihrer Aufzeichnungen oder Videos? Sie können den Support über das untenstehende Formular kontaktieren.<br><br>Nennen Sie die betroffenen Videos und die Art der Qualitätsprobleme. Der Support nimmt so bald wie möglich Kontakt mit Ihnen auf.'
//);
//xoctConf::set(xoctConf::F_REPORT_DATE_TEXT,
//	'Über das untenstehende Formular können Sie dem Support Terminanpassungen an Ihren geplanten Aufzeichnungen melden (z.B. Start- & Endzeit, Datum, Hörsaal).<br><br>Nennen Sie die betroffenen Termine und Ihre Anpassungswünsche. Der Support wird die Anpassungen nach Überprüfung vornehmen. Die Anpassungen werden Ihnen per Email bestätigt.'
//);

?>
<#10>
<?php
xoctPermissionTemplate::updateDB();
//xoctConf::set(xoctConf::F_VIDEO_PORTAL_TITLE, 'Video Portal');
?>
<#11>
<?php
xoctPermissionTemplate::updateDB();
?>
<#12>
<?php
xoctReport::updateDB();
?>
<#13>
<?php
WorkflowParameter::updateDB();
SeriesWorkflowParameter::updateDB();
?>
<#14>
<?php
/**
 * define standard workflow parameters as they were hard-coded before
 */
if (WorkflowParameter::count() === 0) {
	$params = [];
	$params[] = (new WorkflowParameter())
		->setId('flagForCutting')
		->setTitle('Flag for Cutting')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('flagForReview')
		->setTitle('Flag for Review')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('publishToEngage')
		->setTitle('Publish to Engage')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('publishToHarvesting')
		->setTitle('Publish to Harvesting')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_INACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('straightToPublishing')
		->setTitle('Straight to Publishing')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_ACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_ACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('publishToApi')
		->setTitle('Publish to API')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_ACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_ALWAYS_ACTIVE)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	$params[] = (new WorkflowParameter())
		->setId('autopublish')
		->setTitle('Automatisch Publizieren')
		->setDefaultValueMember(WorkflowParameter::VALUE_ALWAYS_ACTIVE)
		->setDefaultValueAdmin(WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET)
		->setType(WorkflowParameter::TYPE_CHECKBOX)
		->create();
	SeriesWorkflowParameter::truncateDB();
	SeriesWorkflowParameterRepository::getInstance()->createParamsForAllObjects($params);
}
?>
<#15>
<?php
xoctOpenCast::updateDB();
xoctUserSetting::updateDB();
?>
<#16>
<?php
xoctUserSetting::updateDB();
?>
<#17>
<?php
\srag\Plugins\Opencast\Chat\Model\ChatroomAR::updateDB();
\srag\Plugins\Opencast\Chat\Model\MessageAR::updateDB();
\srag\Plugins\Opencast\Chat\Model\TokenAR::updateDB();
\srag\Plugins\Opencast\Chat\Model\ConfigAR::updateDB();
?>
<#18>
<?php
xoctOpenCast::updateDB();
?>
<#19>
<?php
global $DIC;
$DIC->database()->query('ALTER TABLE sr_chat_message MODIFY message varchar(512)');
?>
<#20>
<?php
/**
 * the api publication is not used
 */
(new \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository())->delete('api');
?>
<#21>
<?php
/**
 * publications can alternatively search for tags now, so we set all publications to
 * 'flavor', to keep the existing behavior
 */
\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
/** @var PublicationUsage $publication_usage */
foreach (\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::get() as $publication_usage) {
	$publication_usage->setSearchKey(xoctPublicationUsageFormGUI::F_FLAVOR);
	$publication_usage->update();
}
?>
<#22>
<?php
/**
 * to keep the existing behavior:
 * if the internal player is active, change player publication to search for media
 * with the tag 'engage-streaming' (that was hard-coded until now)
 */
\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
if (xoctConf::getConfig(xoctConf::F_INTERNAL_VIDEO_PLAYER)) {
    // to keep the existing behavior
    $player_pub = (new \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository())->getUsage(\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_PLAYER);
    if (!is_null($player_pub)) {
        $player_pub->setMdType(\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::MD_TYPE_MEDIA);
        $player_pub->setSearchKey(xoctPublicationUsageFormGUI::F_TAG);
        $player_pub->setTag('engage-streaming');
        $player_pub->setMdType(\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::MD_TYPE_MEDIA);
        $player_pub->update();
    }
}
?>
<#23>
<?php
//// to keep the existing behavior
//$preview_pub = (new \srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsageRepository())->getUsage(\srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage::USAGE_PREVIEW);
//$player_pub = (new \srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsageRepository())->getUsage(\srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage::USAGE_PLAYER);
//if (is_null($preview_pub) && !is_null($player_pub)) {
//	$preview_pub = new \srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage();
//	$preview_pub->setTitle('Preview');
//	$preview_pub->setChannel($player_pub->getChannel());
//	$preview_pub->setUsageId(\srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage::USAGE_PREVIEW);
//    $preview_pub->setMdType(\srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage::MD_TYPE_ATTACHMENT);
//    $preview_pub->setSearchKey(xoctPublicationUsageFormGUI::F_FLAVOR);
//    $preview_pub->setFlavor('/player+preview');
//    $preview_pub->store();
//}
?>
<#24>
<?php
\srag\Plugins\Opencast\Model\Workflow\WorkflowAR::updateDB();
?>
<#25>
<?php
/**
 * change segment pub md type to attachment if existent
 */
$repository = new \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository();
$usage_segments = \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_SEGMENTS;
$segments_pub = $repository->getUsage($usage_segments);
if (!is_null($segments_pub)) {
	$segments_pub->setMdType(\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::MD_TYPE_ATTACHMENT);
	$segments_pub->update();
}
?>
<#26>
<?php
\srag\Plugins\Opencast\Model\Workflow\WorkflowAR::updateDB();
?>
<#27>
<?php
/**
 * usage 'api' is not used anymore
 */
$repository = new \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository();
$api_pub = $repository->getUsage('api');
if (!is_null($api_pub)) {
    $api_pub->delete();
}
?>
<#28>
<?php
xoctConf::set(xoctConf::F_COMMON_IDP, 1);
?>
<#29>
<?php
// combine owner role prefix ext & email
$is_mapping_email = xoctConf::getConfig(xoctConf::F_USER_MAPPING) == xoctUser::MAP_EMAIL;
$role_owner_prefix = xoctConf::getConfig($is_mapping_email ? 'role_ivt_email_prefix' : 'role_ivt_external_prefix');
if ($role_owner_prefix) {
    xoctConf::set(xoctConf::F_ROLE_OWNER_PREFIX, $role_owner_prefix);
}
?>
<#30>
<?php
\srag\Plugins\Opencast\Cache\Service\DB\DBCacheAR::updateDB();
?>
<#31>
<?php
\srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR::updateDB();
\srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR::updateDB();
?>