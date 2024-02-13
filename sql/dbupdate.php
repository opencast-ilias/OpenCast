<#1>
<?php
$fields = array(
    'usage_id' => array(
        'type' => 'text',
        'length' => '64',

    ),
    'title' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'display_name' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'description' => array(
        'type' => 'text',
        'length' => '4000',

    ),
    'group_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'channel' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'status' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'search_key' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'flavor' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'tag' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'md_type' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'allow_multiple' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'mediatype' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'ignore_object_setting' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'ext_dl_source' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_publication_usage')) {
    $ilDB->createTable('xoct_publication_usage', $fields);
    $ilDB->addPrimaryKey('xoct_publication_usage', array( 'usage_id' ));

}


$fields = array(
    'name' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '250',

    ),
    'value' => array(
        'type' => 'text',
        'length' => '4000',

    ),

);
if (! $ilDB->tableExists('xoct_config')) {
    $ilDB->createTable('xoct_config', $fields);
    $ilDB->addPrimaryKey('xoct_config', array( 'name' ));

}


$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'serie_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'title' => array(
        'type' => 'text',
        'length' => '1024',

    ),
    'description' => array(
        'type' => 'text',
        'length' => '4000',

    ),
    'status' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_group')) {
    $ilDB->createTable('xoct_group', $fields);
    $ilDB->addPrimaryKey('xoct_group', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_group')) {
        $ilDB->createSequence('xoct_group');
    }

}

$fields = array(
    'obj_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'series_identifier' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'intro_text' => array(
        'type' => 'text',
        'length' => '4000',

    ),
    'use_annotations' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'streaming_only' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'permission_per_clip' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'permission_allow_set_own' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'agreement_accepted' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'obj_online' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'default_view' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'view_changeable' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'chat_active' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_data')) {
    $ilDB->createTable('xoct_data', $fields);
    $ilDB->addPrimaryKey('xoct_data', array( 'obj_id' ));

    if (! $ilDB->sequenceExists('xoct_data')) {
        $ilDB->createSequence('xoct_data');
    }

}

?>
<#2>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'group_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'status' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_group_participant')) {
    $ilDB->createTable('xoct_group_participant', $fields);
    $ilDB->addPrimaryKey('xoct_group_participant', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_group_participant')) {
        $ilDB->createSequence('xoct_group_participant');
    }

}

$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'event_identifier' => array(
        'type' => 'text',
        'length' => '128',

    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'owner_id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'status' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_invitations')) {
    $ilDB->createTable('xoct_invitations', $fields);
    $ilDB->addPrimaryKey('xoct_invitations', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_invitations')) {
        $ilDB->createSequence('xoct_invitations');
    }

}


?>
<#3>
<?php
$fields = array(
    'id' => array(
        'type' => 'text',
        'length' => '64',

    ),
    'is_online' => array(
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_event_additions')) {
    $ilDB->createTable('xoct_event_additions', $fields);
    $ilDB->addPrimaryKey('xoct_event_additions', array( 'id' ));

}


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
// \srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB(); no longer needed
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
//\srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB(); no longer needed
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
//\srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB(); no longer needed
//xoctConf::set(xoctConf::F_VIDEO_PORTAL_TITLE, 'Video Portal');
?>
<#11>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'sort' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'is_default' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'title_de' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'title_en' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'info_de' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'info_en' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'role' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'read_access' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'write_access' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'additional_acl_actions' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'additional_actions_download' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'additional_actions_annotate' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'added_role' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'added_role_name' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'added_role_read_access' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'added_role_write_access' => array(
        'type' => 'integer',
        'length' => '1',

    ),
    'added_role_acl_actions' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'added_role_actions_download' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'added_role_actions_annotate' => array(
        'type' => 'text',
        'length' => '256',

    ),

);
if (! $ilDB->tableExists('xoct_perm_template')) {
    $ilDB->createTable('xoct_perm_template', $fields);
    $ilDB->addPrimaryKey('xoct_perm_template', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_perm_template')) {
        $ilDB->createSequence('xoct_perm_template');
    }

}
?>
<#12>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'user_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'created_at' => array(
        'notnull' => '1',
        'type' => 'timestamp',

    ),
    'type' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'ref_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'event_id' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'subject' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'message' => array(
        'notnull' => '1',
        'type' => 'clob',

    ),

);
if (! $ilDB->tableExists('xoct_report')) {
    $ilDB->createTable('xoct_report', $fields);
    $ilDB->addPrimaryKey('xoct_report', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_report')) {
        $ilDB->createSequence('xoct_report');
    }

}

?>
<#13>
<?php
$fields = array(
    'id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '250',

    ),
    'title' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'type' => array(
        'type' => 'text',
        'length' => '256',

    ),
    'default_value_member' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'default_value_admin' => array(
        'type' => 'integer',
        'length' => '8',

    ),

);
if (! $ilDB->tableExists('xoct_workflow_param')) {
    $ilDB->createTable('xoct_workflow_param', $fields);
    $ilDB->addPrimaryKey('xoct_workflow_param', array( 'id' ));

}

$fields = array(
    'id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'obj_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'param_id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '250',

    ),
    'value_member' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'value_admin' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),

);
if (! $ilDB->tableExists('xoct_series_param')) {
    $ilDB->createTable('xoct_series_param', $fields);
    $ilDB->addPrimaryKey('xoct_series_param', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_series_param')) {
        $ilDB->createSequence('xoct_series_param');
    }

}

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
//\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
$fields = array(
    'id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'ref_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'user_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'name' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '56',

    ),
    'value' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),

);
if (! $ilDB->tableExists('xoct_user_setting')) {
    $ilDB->createTable('xoct_user_setting', $fields);
    $ilDB->addPrimaryKey('xoct_user_setting', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_user_setting')) {
        $ilDB->createSequence('xoct_user_setting');
    }

}

?>
<#16>
<?php
//\srag\Plugins\Opencast\Model\UserSettings\UserSetting::updateDB();
?>
<#17>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'event_id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '56',

    ),
    'obj_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),

);
if (! $ilDB->tableExists('sr_chat_room')) {
    $ilDB->createTable('sr_chat_room', $fields);
    $ilDB->addPrimaryKey('sr_chat_room', array( 'id' ));

    if (! $ilDB->sequenceExists('sr_chat_room')) {
        $ilDB->createSequence('sr_chat_room');
    }

}

$fields = array(
    'id' => array(
        'type' => 'text',
        'length' => '56',

    ),
    'chat_room_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'usr_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'message' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '512',

    ),
    'sent_at' => array(
        'notnull' => '1',
        'type' => 'timestamp',

    ),

);
if (! $ilDB->tableExists('sr_chat_message')) {
    $ilDB->createTable('sr_chat_message', $fields);
    $ilDB->addPrimaryKey('sr_chat_message', array( 'id' ));

}

$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'chat_room_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'usr_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),
    'public_name' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '128',

    ),
    'token' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '128',

    ),
    'valid_until_unix' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '8',

    ),

);
if (! $ilDB->tableExists('sr_chat_token')) {
    $ilDB->createTable('sr_chat_token', $fields);
    $ilDB->addPrimaryKey('sr_chat_token', array( 'id' ));

    if (! $ilDB->sequenceExists('sr_chat_token')) {
        $ilDB->createSequence('sr_chat_token');
    }

}
$fields = array(
    'name' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '250',

    ),
    'value' => array(
        'type' => 'text',
        'length' => '4000',

    ),

);
if (! $ilDB->tableExists('sr_chat_config')) {
    $ilDB->createTable('sr_chat_config', $fields);
    $ilDB->addPrimaryKey('sr_chat_config', array( 'name' ));

}


?>
<#18>
<?php
//\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
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
//\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
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
//\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();
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
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'workflow_id' => array(
        'type' => 'text',
        'length' => '64',

    ),
    'title' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'description' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'tags' => array(
        'type' => 'text',
        'length' => '512',

    ),
    'config_panel' => array(
        'type' => 'clob',

    ),

);
if (! $ilDB->tableExists('xoct_workflow')) {
    $ilDB->createTable('xoct_workflow', $fields);
    $ilDB->addPrimaryKey('xoct_workflow', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_workflow')) {
        $ilDB->createSequence('xoct_workflow');
    }

}
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
//\srag\Plugins\Opencast\Model\Workflow\WorkflowAR::updateDB();
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
global $DIC;
$fields = [
    'identifier' => [
        'notnull' => true,
        'type' => 'text',
        'length' => 128,

    ],
    'value' => [
        'notnull' => true,
        'type' => 'clob',

    ],
    'expires' => [
        'type' => 'integer',
        'length' => 8,

    ],
];
if (! $DIC->database()->tableExists('xoct_cache')) {
    $DIC->database()->createTable('xoct_cache', $fields);
    $DIC->database()->addPrimaryKey('xoct_cache', ['identifier']);
}
?>
<#31>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'field_id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '128',

    ),
    'title_de' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'title_en' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'visible_for_permissions' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '512',

    ),
    'required' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',

    ),
    'read_only' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',

    ),
    'prefill' => array(
        'type' => 'text',
        'length' => '4000',

    ),
    'sort' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'values' => array(
        'type' => 'text',

    ),

);
if (! $ilDB->tableExists('xoct_md_field_event')) {
    $ilDB->createTable('xoct_md_field_event', $fields);
    $ilDB->addPrimaryKey('xoct_md_field_event', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_md_field_event')) {
        $ilDB->createSequence('xoct_md_field_event');
    }

}
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'field_id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '128',

    ),
    'title_de' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'title_en' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '256',

    ),
    'visible_for_permissions' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '512',

    ),
    'required' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',

    ),
    'read_only' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',

    ),
    'prefill' => array(
        'type' => 'text',
        'length' => '4000',

    ),
    'sort' => array(
        'type' => 'integer',
        'length' => '8',

    ),
    'values' => array(
        'type' => 'text',

    ),

);
if (! $ilDB->tableExists('xoct_md_field_series')) {
    $ilDB->createTable('xoct_md_field_series', $fields);
    $ilDB->addPrimaryKey('xoct_md_field_series', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_md_field_series')) {
        $ilDB->createSequence('xoct_md_field_series');
    }

}
// \srag\Plugins\Opencast\Model\Cache\CacheFactory::getInstance()->flush(); removed since a new caching mechanism is used
?>
<#32>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',

    ),
    'user_id' => array(
        'notnull' => '1',
        'type' => 'integer',

    ),
    'oc_instance_id' => array(
        'notnull' => '1',
        'type' => 'integer',

    ),
    'tou_accepted' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',

    ),

);
if (! $ilDB->tableExists('xoct_accepted_tou')) {
    $ilDB->createTable('xoct_accepted_tou', $fields);
    $ilDB->addPrimaryKey('xoct_accepted_tou', array( 'id' ));

    if (! $ilDB->sequenceExists('xoct_accepted_tou')) {
        $ilDB->createSequence('xoct_accepted_tou');
    }

}


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
//\srag\Plugins\Opencast\Model\Object\ObjectSettings::updateDB();
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
    // The paella player live config option is discarded after paella 7 update, but the code remains here to keep track of flow.
    // \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
    //     \srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_OPTION_LIVE,
    //     \srag\Plugins\Opencast\Model\Config\PluginConfig::PAELLA_OPTION_DEFAULT
    // );
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
$ilDB->update(
    'il_plugin',
    ['last_update_version' => ['text', '4.0.2-oc']],
    ['plugin_id' => ['text', 'xoct']]
);
?>
<#38>
<?php
//srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate::updateDB();
?>
<#39>
<?php
/** @var $ilDB ilDBInterface */

$field_infos = [
    'type' => 'clob',
    'notnull' => false,
    'default' => null
];

if (!$ilDB->tableColumnExists('xoct_md_field_event', 'values')) {
    $ilDB->addTableColumn('xoct_md_field_event', 'values', $field_infos);
}

if (!$ilDB->tableColumnExists('xoct_md_field_series', 'values')) {
    $ilDB->addTableColumn('xoct_md_field_series', 'values', $field_infos);
}

?>
<#40>
<?php
$ilDB->manipulate("update xoct_data set intro_text = '' where intro_text is null");
?>
<#41>
<?php
/** @var $ilDB ilDBInterface */
$res = $ilDB->queryF('SELECT value FROM xoct_config WHERE name = %s', ['text'], ['curl_chunk_size']);
if ($res->rowCount() === 0) {
    $ilDB->insert('xoct_config', [
        'name' => ['text', 'curl_chunk_size'],
        'value' => ['text', '20']
    ]);
}
?>
<#42>
<?php
// Adding paella player default values.
if (empty(\srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_LIVESTREAM_TYPE))) {
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
        \srag\Plugins\Opencast\Model\Config\PluginConfig::F_LIVESTREAM_TYPE,
        'hls'
    );
}
if (empty(\srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_THEME))) {
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
        \srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_THEME,
        \srag\Plugins\Opencast\Model\Config\PluginConfig::PAELLA_OPTION_DEFAULT
    );
}
if (empty(\srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig(\srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_THEME_LIVE))) {
    \srag\Plugins\Opencast\Model\Config\PluginConfig::set(
        \srag\Plugins\Opencast\Model\Config\PluginConfig::F_PAELLA_THEME_LIVE,
        \srag\Plugins\Opencast\Model\Config\PluginConfig::PAELLA_OPTION_DEFAULT
    );
}
?>
<#43>
<?php
// Introducing xoct_publication_group table with model PublicationUsageGroup for grouping PublicationUsage.
if (!$ilDB->tableExists('xoct_publication_group')) {
    $fields = [
        "id" => ["notnull" => true, "length" => 4, "type" => "integer"],
        "name" => ["notnull" => true, "length" => 512, "type" => "text"],
        "display_name" => ["notnull" => false, "length" => 512, "type" => "text"],
        "description" => ["notnull" => false, "length" => 4000, "type" => "text"],
    ];
    $ilDB->createTable("xoct_publication_group", $fields);
    $ilDB->createSequence('xoct_publication_group');
    $ilDB->addPrimaryKey('xoct_publication_group', ['id']);
}
// Introducing xoct_pub_sub_usage table with model PublicationSubUsage as for sub usages.
if (!$ilDB->tableExists('xoct_pub_sub_usage')) {
    $fields = [
        "id" => ['notnull' => true, "length" => 4, "type" => "integer"],
        "parent_usage_id" => ["notnull" => true, "length" => 64, "type" => "text"],
        "title" => ["notnull" => false, "length" => 512, "type" => "text"],
        "display_name" => ["notnull" => false, "length" => 512, "type" => "text"],
        "description" => ["notnull" => false, "length" => 4000, "type" => "text"],
        "group_id" => ['notnull' => false, 'length' => 8, "type" => "integer"],
        "channel" => ["notnull" => false, "length" => 512, "type" => "text"],
        "status" => ['notnull' => false, 'length' => 1, 'type' => "integer"],
        "search_key" => ["notnull" => false, "length" => 512, "type" => "text"],
        "flavor" => ["notnull" => false, "length" => 512, "type" => "text"],
        "tag" => ["notnull" => false, "length" => 512, "type" => "text"],
        "md_type" => ['notnull' => false, 'length' => 1, 'type' => "integer", 'default' => null],
        "allow_multiple" => ['notnull' => false, 'length' => 1, 'type' => "integer", 'default' => 0],
        "mediatype" => ["notnull" => false, "length" => 512, "type" => "text"],
        "ignore_object_setting" => ['notnull' => false, 'length' => 1, 'type' => "integer", 'default' => 0],
        "ext_dl_source" => ['notnull' => false, 'length' => 1, 'type' => "integer", 'default' => 0],
    ];
    $ilDB->createTable("xoct_pub_sub_usage", $fields);
    $ilDB->createSequence('xoct_pub_sub_usage');
    $ilDB->addPrimaryKey('xoct_pub_sub_usage', ['id']);
}
// Add new columns to PublicationUsage.
//\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::updateDB();

foreach (\srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::get() as $publication_usage) {
    if ($publication_usage->getUsageId() == \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_DOWNLOAD || $publication_usage->getUsageId() == \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_DOWNLOAD_FALLBACK) {
        $ext_dl_source = false;
        $config = \srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig('external_download_source');
        if ((bool) $config) {
            $ext_dl_source = true;
        }
        $publication_usage->setExternalDownloadSource($ext_dl_source);
        $publication_usage->update();
    }
}

foreach (\srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage::get() as $publication_subusage) {
    if ($publication_subusage->getParentUsageId() == \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_DOWNLOAD || $publication_subusage->getParentUsageId() == \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage::USAGE_DOWNLOAD_FALLBACK) {
        $ext_dl_source = false;
        $config = \srag\Plugins\Opencast\Model\Config\PluginConfig::getConfig('external_download_source');
        if ((bool) $config) {
            $ext_dl_source = true;
        }
        $publication_subusage->setExternalDownloadSource($ext_dl_source);
        $publication_subusage->update();
    }
}
?>
<#44>
<?php
// To apply new changes into WorkflowAP model as well as xoct_workflow table.
//\srag\Plugins\Opencast\Model\Workflow\WorkflowAR::updateDB();
?>
<#45>
<?php
// The small column changes must be applied.
//\srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR::updateDB();
//\srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR::updateDB();
// Since we get rid of MDPrefillOption, we need to update the prefill column on both
// MDFieldConfigEventAR & MDFieldConfigSeriesAR models.
$mapping = [
    'none' => '',
    'crs_title' => '[COURSE.TITLE]',
    'username_creator' => '[USER.FIRSTNAME] [USER.LASTNAME]'
];
foreach (\srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR::get() as $md_config_event) {
    if (isset($mapping[$md_config_event->getPrefill()])) {
        $md_config_event->setPrefill($mapping[$md_config_event->getPrefill()]);
        $md_config_event->update();
    }
}
foreach (\srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR::get() as $md_config_series) {
    if (isset($mapping[$md_config_series->getPrefill()])) {
        $md_config_series->setPrefill($mapping[$md_config_series->getPrefill()]);
        $md_config_series->update();
    }
}
?>
