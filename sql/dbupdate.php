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
$xoct_type_id = ilDBUpdateNewObjectType::addNewType('xoct', 'Plugin OpenCast');

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
if (!xoctConf::get(xoctConf::F_STD_ROLES)) {
	$std_roles = array();
	$std_roles[] = xoctConf::get('role_ext_application');
	$std_roles[] = xoctConf::get('role_producer');
	$std_roles = array_filter($std_roles);
	if (!empty($std_roles)) {
		xoctConf::set(xoctConf::F_STD_ROLES, $std_roles);
	}
}
?>