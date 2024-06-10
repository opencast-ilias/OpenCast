<?php

declare(strict_types=1);

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;

/**
 * Class ilObjOpenCast
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 */
class ilObjOpenCast extends ilObjectPlugin
{
    /**
     * @var bool
     */
    protected $object;
    public const DEV = false;
    /**
     * @var \ilCtrl
     */
    private $ctrl;

    /**
     * @param int $a_ref_id
     */
    public function __construct($a_ref_id = 0)
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        parent::__construct($a_ref_id);
    }

    final protected function initType(): void
    {
        $this->setType(ilOpenCastPlugin::PLUGIN_ID);
    }

    protected function doCreate(bool $clone_mode = false): void
    {
    }

    public function updateObjectFromSeries(Metadata $metadata): void
    {
        PluginConfig::setApiSettings();
        if ($this->ctrl->isAsynch()) {
            // don't update title/description on async calls
            return;
        }

        $title = $metadata->getField(MDFieldDefinition::F_TITLE)->getValue();
        $description = $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue();
        if ($title != $this->getTitle() || $description != $this->getDescription()) {
            $this->setTitle($title);
            $this->setDescription($description);
            $this->update();
        }
    }

    protected function doUpdate(): void
    {
    }

    protected function doDelete(): void
    {
        $opencast_dic = OpencastDIC::getInstance();
        /** @var ObjectSettings $objectSettings */
        $objectSettings = ObjectSettings::find($this->getId());
        if ($objectSettings) {
            $opencast_dic->paella_config_storage_service()->delete($objectSettings->getPaellaPlayerFileId());
            $opencast_dic->paella_config_storage_service()->delete($objectSettings->getPaellaPlayerLiveFileId());
            $objectSettings->delete();
        }
        foreach (PermissionGroup::where(['serie_id' => $this->getId()])->get() as $ivt_group) {
            $ivt_group->delete();
        }
    }

    /**
     * @param ilObjOpenCast $new_obj
     * @param               $a_target_id
     * @param null          $a_copy_id
     *
     * @return bool|void
     */
    #[ReturnTypeWillChange]
    protected function doCloneObject(/*ilObject2*/ $new_obj, /*int*/ $a_target_id, /*?int*/ $a_copy_id = null): void
    {

        // Just in case, the main toggle variable to allow duplication "ALLOW_DUPLICATION" is enabled,
        // then the actual cloning the object takes place!
        if (ilOpenCastPlugin::ALLOW_DUPLICATION) {
            PluginConfig::setApiSettings();
            /**
             * @var $new_object_settings ObjectSettings
             * @var $existing_object_settings ObjectSettings
             */
            $new_object_settings = new ObjectSettings();
            $new_object_settings->setObjId($new_obj->getId());
            $existing_object_settings = ObjectSettings::find($this->getId());
            if ($existing_object_settings === null) {
                return;
            }

            $new_object_settings->setSeriesIdentifier($existing_object_settings->getSeriesIdentifier());
            $new_object_settings->setIntroductionText($existing_object_settings->getIntroductionText());
            $new_object_settings->setAgreementAccepted($existing_object_settings->getAgreementAccepted());
            $new_object_settings->setOnline(false);
            $new_object_settings->setPermissionAllowSetOwn($existing_object_settings->getPermissionAllowSetOwn());
            $new_object_settings->setUseAnnotations($existing_object_settings->getUseAnnotations());
            $new_object_settings->setPermissionPerClip($existing_object_settings->getPermissionPerClip());

            $new_object_settings->create();
        }
    }

    public function getParentCourseOrGroup()
    {
        return self::_getParentCourseOrGroup($this->ref_id);
    }

    /**

     * @return null|ilObjCourse|ilObjGroup
     */
    public static function _getParentCourseOrGroup(int $ref_id): ?ilContainer
    {
        global $DIC;
        static $crs_or_grp_cache;
        if (!is_array($crs_or_grp_cache)) {
            $crs_or_grp_cache = [];
        }

        if (isset($crs_or_grp_cache[$ref_id])) {
            return $crs_or_grp_cache[$ref_id];
        }

        while (!in_array(ilObject2::_lookupType($ref_id, true), ['crs', 'grp'])) {
            if ($ref_id === 1) {
                return $crs_or_grp_cache[$ref_id] = null;
            }
            $ref_id = (int) $DIC->repositoryTree()->getParentId($ref_id);
        }

        /** @var ilObjCourse|ilObjGroup $course_or_group */
        $course_or_group = ilObjectFactory::getInstanceByRefId($ref_id);
        return $crs_or_grp_cache[$ref_id] = $course_or_group;
    }

    public static function _getCourseOrGroupRole(): string
    {
        global $DIC;
        $user = $DIC->user();
        $rbac_review = $DIC->rbac()->review();
        $ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
        $crs_or_group = self::_getParentCourseOrGroup($ref_id);
        if ($crs_or_group === null) {
            return 'Unbekannt';
        }

        if ($rbac_review->isAssigned($user->getId(), $crs_or_group->getDefaultAdminRole())) {
            return $crs_or_group instanceof ilObjCourse ? 'Kursadministrator' : 'Gruppenadministrator';
        }
        if ($rbac_review->isAssigned($user->getId(), $crs_or_group->getDefaultMemberRole())) {
            return $crs_or_group instanceof ilObjCourse ? 'Kursmitglied' : 'Gruppenmitglied';
        }
        if (($crs_or_group instanceof ilObjCourse) && $rbac_review->isAssigned(
            $user->getId(),
            $crs_or_group->getDefaultTutorRole()
        )) {
            return 'Kurstutor';
        }

        return 'Unbekannt';
    }
}
