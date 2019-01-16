<#1>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PublicationUsage/class.xoctPublicationUsage.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/SystemAccount/class.xoctSystemAccount.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctIVTGroup.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php');

xoctPublicationUsage::updateDB();
xoctSystemAccount::updateDB();
xoctConf::updateDB();
xoctIVTGroup::updateDB();
xoctOpenCast::updateDB();
?>
<#2>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctIVTGroupParticipant.php');
xoctIVTGroupParticipant::updateDB();
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Invitations/class.xoctInvitation.php');
xoctInvitation::updateDB();
?>
<#3>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Event/class.xoctEventAdditions.php');
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
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php');
xoctOpenCast::updateDB();
?>
<#6>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
if (!xoctConf::getConfig(xoctConf::F_STD_ROLES)) {
	$std_roles = array();
	$std_roles[] = xoctConf::getConfig('role_ext_application');
	$std_roles[] = xoctConf::getConfig('role_producer');
	$std_roles = array_filter($std_roles);
	if (!empty($std_roles)) {
		xoctConf::set(xoctConf::F_STD_ROLES, $std_roles);
	}
}
?>
<#7>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
xoctConf::set(xoctConf::F_SIGN_PLAYER_LINKS, 1);
xoctConf::set(xoctConf::F_SIGN_DOWNLOAD_LINKS, 1);
xoctConf::set(xoctConf::F_SIGN_THUMBNAIL_LINKS, 1);
?>
<#8>
<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PermissionTemplates/class.xoctPermissionTemplate.php';
xoctPermissionTemplate::updateDB();
?>
<#9>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
xoctConf::set(xoctConf::F_REPORT_QUALITY_TEXT,
	'Haben Sie Qualitätsprobleme mit dem Bild oder Ton Ihrer Aufzeichnungen oder Videos? Sie können den Support über das untenstehende Formular kontaktieren.<br><br>Nennen Sie die betroffenen Videos und die Art der Qualitätsprobleme. Der Support nimmt so bald wie möglich Kontakt mit Ihnen auf.'
);
xoctConf::set(xoctConf::F_REPORT_DATE_TEXT,
	'Über das untenstehende Formular können Sie dem Support Terminanpassungen an Ihren geplanten Aufzeichnungen melden (z.B. Start- & Endzeit, Datum, Hörsaal).<br><br>Nennen Sie die betroffenen Termine und Ihre Anpassungswünsche. Der Support wird die Anpassungen nach Überprüfung vornehmen. Die Anpassungen werden Ihnen per Email bestätigt.'
);

?>
<#10>
<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PermissionTemplates/class.xoctPermissionTemplate.php';
xoctPermissionTemplate::updateDB();
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
xoctConf::set(xoctConf::F_VIDEO_PORTAL_TITLE, 'Video Portal');
?>
<#11>
<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PermissionTemplates/class.xoctPermissionTemplate.php';
xoctPermissionTemplate::updateDB();
?>
<#12>
<?php
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/Reports/class.xoctReport.php';
xoctReport::updateDB();
?>