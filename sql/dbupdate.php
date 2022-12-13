<#1>
<?php

\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
\srag\Plugins\Opencast\Model\Config\PluginConfig::updateDB();
\srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup::updateDB();
\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
?>
<#2>
<?php
\srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroupParticipant::updateDB();
\srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant::updateDB();
?>
<#3>
<?php
srag\Plugins\Opencast\Model\Event\EventAdditionsAR::updateDB();
?>
<#4>
<?php
require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");
$xoct_type_id = ilDBUpdateNewObjectType::addNewType(ilOpenCastPlugin::PLUGIN_ID, 'Plugin OpenCast');

//Adding a new Permission rep_robj_xoct_upload ("Upload")
$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
    'rep_robj_xoct_perm_upload', 'upload', 'object', 2010);
if ($offering_admin) {
    ilDBUpdateNewObjectType::addRBACOperation($xoct_type_id, $offering_admin);
}

//Adding a new Permission rep_robj_xoct_edit_videos ("Edit Videos")
$offering_admin = ilDBUpdateNewObjectType::addCustomRBACOperation( //$a_id, $a_title, $a_class, $a_pos
    'rep_robj_xoct_perm_edit_videos', 'edit videos', 'object', 2020);
if ($offering_admin) {
    ilDBUpdateNewObjectType::addRBACOperation($xoct_type_id, $offering_admin);
}
?>
<#5>
<?php
\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
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
\srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB();
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
\srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB();
//xoctConf::set(xoctConf::F_VIDEO_PORTAL_TITLE, 'Video Portal');
?>
<#11>
<?php
\srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB();
?>
<#12>
<?php
\srag\Plugins\Opencast\Model\Report\Report::updateDB();
?>
<#13>
<?php
\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::updateDB();
\srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameter::updateDB();
?>
<#14>
<?php
/**
 * define standard workflow parameters as they were hard-coded before
 */
if (\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::count() === 0) {
    $params = [];
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('flagForCutting')
        ->setTitle('Flag for Cutting')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('flagForReview')
        ->setTitle('Flag for Review')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('publishToEngage')
        ->setTitle('Publish to Engage')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('publishToHarvesting')
        ->setTitle('Publish to Harvesting')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_INACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('straightToPublishing')
        ->setTitle('Straight to Publishing')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_ACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_ACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('publishToApi')
        ->setTitle('Publish to API')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_ACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_ACTIVE)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    $params[] = (new \srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter())
        ->setId('autopublish')
        ->setTitle('Automatisch Publizieren')
        ->setDefaultValueMember(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_ALWAYS_ACTIVE)
        ->setDefaultValueAdmin(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET)
        ->setType(\srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter::TYPE_CHECKBOX)
        ->create();
    \srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameter::truncateDB();
    global $DIC;
    (new \srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository(
        $DIC->ui()->factory(),
        $DIC->refinery(),
        new \srag\Plugins\Opencast\Model\WorkflowParameter\WorkflowParameterParser()
    ))->createParamsForAllObjects($params);
}
?>
<#15>
<?php
\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
\srag\Plugins\Opencast\Model\UserSettings\UserSetting::updateDB();
?>
<#16>
<?php
\srag\Plugins\Opencast\Model\UserSettings\UserSetting::updateDB();
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
\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
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
/** @var \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage $publication_usage */
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
if (\srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_INTERNAL_VIDEO_PLAYER)) {
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
// is contained in default config xml
//\srag\Plugins\Opencast\Model\Config\PluginConfig::set(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_COMMON_IDP, 1);
?>
<#29>
<?php
// combine owner role prefix ext & email
$is_mapping_email = \srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_USER_MAPPING) == \srag\Plugins\Opencast\Model\User\xoctUser::MAP_EMAIL;
$role_owner_prefix = \srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig($is_mapping_email ? 'role_ivt_email_prefix' : 'role_ivt_external_prefix');
if ($role_owner_prefix) {
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_ROLE_OWNER_PREFIX, $role_owner_prefix);
}
?>
<#30>
<?php
\srag\Plugins\Opencast\Model\Cache\Service\DB\DBCacheAR::updateDB();
?>
<#31>
<?php
\srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR::updateDB();
\srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR::updateDB();
\srag\Plugins\Opencast\Model\Cache\CacheFactory::getInstance()->flush();
?>
<#32>
<?php
\srag\Plugins\Opencast\Model\TermsOfUse\AcceptedToU::updateDB();
?>
<#33>
<?php
global $DIC;
// preconfigure metadata
// event
if ($DIC->database()->query('select id from xoct_md_field_event')->rowCount() === 0) {
    $DIC->database()->insert('xoct_md_field_event', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_event')],
        'field_id' => ['text', 'title'],
        'title_de' => ['text', 'Titel'],
        'title_en' => ['text', 'Title'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 1],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 1],
    ]);
    $DIC->database()->insert('xoct_md_field_event', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_event')],
        'field_id' => ['text', 'description'],
        'title_de' => ['text', 'Beschreibung'],
        'title_en' => ['text', 'Description'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 2],
    ]);
    $DIC->database()->insert('xoct_md_field_event', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_event')],
        'field_id' => ['text', 'location'],
        'title_de' => ['text', 'Aufnahmestation'],
        'title_en' => ['text', 'Recording Station'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 3],
    ]);
    $DIC->database()->insert('xoct_md_field_event', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_event')],
        'field_id' => ['text', 'startDate'],
        'title_de' => ['text', 'Start'],
        'title_en' => ['text', 'Start'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 4],
    ]);
}
// series
if ($DIC->database()->query('select id from xoct_md_field_series')->rowCount() === 0) {
    $DIC->database()->insert('xoct_md_field_series', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_series')],
        'field_id' => ['text', 'title'],
        'title_de' => ['text', 'Titel'],
        'title_en' => ['text', 'Title'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 1],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 1],
    ]);
    $DIC->database()->insert('xoct_md_field_series', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_series')],
        'field_id' => ['text', 'description'],
        'title_de' => ['text', 'Beschreibung'],
        'title_en' => ['text', 'Description'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 0],
        'prefill' => ['text', 'none'],
        'sort' => ['integer', 2],
    ]);
    $DIC->database()->insert('xoct_md_field_series', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_series')],
        'field_id' => ['text', 'creator'],
        'title_de' => ['text', 'Veranstalter'],
        'title_en' => ['text', 'Organizers'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 1],
        'prefill' => ['text', 'crs_title'],
        'sort' => ['integer', 3],
    ]);
    $DIC->database()->insert('xoct_md_field_series', [
        'id' => ['integer', $DIC->database()->nextId('xoct_md_field_series')],
        'field_id' => ['text', 'contributor'],
        'title_de' => ['text', 'Mitwirkende'],
        'title_en' => ['text', 'Contributors'],
        'visible_for_permissions' => ['text', 'all'],
        'required' => ['integer', 0],
        'read_only' => ['integer', 1],
        'prefill' => ['text', 'username_creator'],
        'sort' => ['integer', 4],
    ]);
}
?>
<#34>
<?php
\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
/** @var $objectSettings \srag\Plugins\Opencast\Model\Object\ObjectSettings */
foreach (\srag\Plugins\Opencast\Model\Object\ObjectSettings::get() as $objectSettings) {
    $objectSettings->setPaellaPlayerOption('default');
    $objectSettings->setPaellaPlayerLiveOption('default');
    $objectSettings->update();
}
?>
<#35>
<?php
global $DIC;
$DIC->database()->dropTable('xoct_system_account', false);
?>
<#36>
<?php
// if count is 0 means we are first time installing. a default config will be loaded in that case
if (\srag\Plugins\Opencast\Model\Config\PluginConfig::count() > 0) {
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
        \srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_OPTION,
        \srag\Plugins\Opencast\Model\Config\PluginConfig::PAELLA_OPTION_DEFAULT
    );
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
        \srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_OPTION_LIVE,
        \srag\Plugins\Opencast\Model\Config\PluginConfig::PAELLA_OPTION_DEFAULT
    );
}
?>
<#37>
<?php
// Several users of the Opencast plugins for ILIAS decided in 2022 to continue
// the development of the plugin themselves: https://github.com/opencast-ilias/OpenCast/
// Therefore, besides this version, there is at least one other version of the
// plugin. These are NOT compatible with each other. With this DB-Step we want
// to have a possibility to check if exactly this step was executed once. Of
// course, this does not give any special security to make sure that other versions
// do not try to update to this version. But another puzzle piece we can use.
/** @var $ilDB ilDBInterface */
$ilDB->insert('xoct_config', [
    'name' => ['text', 'version_check'],
    'value' => ['text', '44ac530093a998b525b0a73ba536e64f03bbaff47446cf99e1a31d6a042a4549']
]);
$ilDB->update('il_plugin',
    ['last_update_version' => ['text', '4.0.2-oc']],
    ['plugin_id' => ['text', 'xoct']]
);
?>
<#38>
<?php
srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB();
?>