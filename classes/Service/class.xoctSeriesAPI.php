<?php
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\API\Group\Group;

/**
 * Class xoctSeriesAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesAPI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var self
	 */
	protected static $instance;


	/**
	 * xoctSeriesAPI constructor.
	 */
	public function __construct() {
	}


	/**
	 * @return xoctSeriesAPI
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * possible additional data:
	 *
	 *  owner => integer
	 *  permission_template_id => integer
	 *  series_id => text
	 *  description => text
	 *  online => boolean
	 *  introduction_text => text
	 *  license => text
	 *  use_annotations => boolean
	 *  streaming_only => boolean
	 *  permission_per_clip => boolean
	 *  permission_allow_set_own => boolean
	 *  member_upload => boolean
	 *
	 *
	 * @param       $parent_ref_id
	 * @param       $title
	 * @param array $additional_data
	 *
	 * @return xoctOpenCast
	 * @throws xoctInternalApiException
	 */
	public function create($parent_ref_id, $title, $additional_data = array()) {
		$parent_type = ilObject2::_lookupType($parent_ref_id, true);
		if (!self::dic()->objDefinition()->isContainer($parent_type)) {
			throw new xoctInternalApiException("object with parent_ref_id $parent_ref_id is of type $parent_type but should be a container");
		}
		if (!ilObjOpenCast::_getParentCourseOrGroup($parent_ref_id)) {
			throw new xoctInternalApiException("object with parent_ref_id $parent_ref_id is not a course/group or inside a course/group");
		}

        $object = new ilObjOpenCast();
        if (isset($additional_data['owner'])) {
            $object->setOwner($additional_data['owner']);
        }
        $object->setTitle($title);
        $object->setDescription(isset($additional_data['description']) ? $additional_data['description'] : '');
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);

		$cast = new xoctOpenCast();
		$cast->setOnline(isset($additional_data['online']) ? $additional_data['online'] : false);
		$cast->setAgreementAccepted(true);
		$cast->setIntroductionText(isset($additional_data['introduction_text']) ? $additional_data['introduction_text'] : '');
		$cast->setUseAnnotations(isset($additional_data['use_annotations']) ? $additional_data['use_annotations'] : false);
		$cast->setStreamingOnly(isset($additional_data['streaming_only']) ? $additional_data['streaming_only'] : false);
		$cast->setPermissionPerClip(isset($additional_data['permission_per_clip']) ? $additional_data['permission_per_clip'] : false);
		$cast->setPermissionAllowSetOwn(isset($additional_data['permission_allow_set_own']) ? $additional_data['permission_allow_set_own'] : false);

		$series = $cast->getSeries();
		$series->setIdentifier(isset($additional_data['series_id']) ? $additional_data['series_id'] : '');
		$series->setTitle($title);
		$series->setDescription(isset($additional_data['description']) ? $additional_data['description'] : '');
		$series->setLicense(isset($additional_data['license']) ? $additional_data['license'] : '');

		$std_acls = new xoctAclStandardSets();
		$series_acls = $std_acls->getAcls();
		if (isset($additional_data['permission_template_id'])) {
			xoctPermissionTemplate::removeAllTemplatesFromAcls($series_acls);
			/** @var xoctPermissionTemplate $xoctPermissionTemplate */
			$xoctPermissionTemplate = xoctPermissionTemplate::find($additional_data['permission_template_id']);
			$xoctPermissionTemplate->addToAcls($series_acls, !$cast->getStreamingOnly(), $cast->getUseAnnotations());
		} elseif ($default_template = xoctPermissionTemplate::where(array('is_default' => 1))->first()) {
            /** @var xoctPermissionTemplate $default_template */
            $default_template->addToAcls($series_acls, !$cast->getStreamingOnly(), $cast->getUseAnnotations());
        }

        $series->setAccessPolicies($series_acls);

		// add producers
        $producers = ilObjOpenCastAccess::getProducersForRefID($object->getRefId());
        if (isset($additional_data['owner'])) {
            $producers[] = xoctUser::getInstance($additional_data['owner']);
        }

        try {
            $ilias_producers = Group::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
            $ilias_producers->addMembers($producers);
        } catch (xoctException $e) {
        }

        $series->addProducers($producers, true);
        $series->addOrganizer(ilObjOpencast::_getParentCourseOrGroup($object->getRefId())->getTitle(), true);

		if ($series->getIdentifier()) {
			$series->update();
		} else {
			$series->create();
		}

		$cast->setSeriesIdentifier($series->getIdentifier());
        $cast->setObjId($object->getId());
        $cast->create(true);

		//member upload
		if (isset($additional_data['member_upload'])) {
			ilObjOpenCastAccess::activateMemberUpload($object->getRefId());
		}

		xoctSeriesWorkflowParameterRepository::getInstance()->syncAvailableParameters($object->getId());

		return $cast;
	}


	/**
	 * @param $ref_id
	 *
	 * @return xoctOpenCast
	 */
	public function read($ref_id) {
		/** @var xoctOpenCast $cast */
		$cast = xoctOpenCast::find(ilObjOpenCast::_lookupObjectId($ref_id));
		return $cast;
	}


	/**
	 * @param      $ref_id
	 * @param bool $delete_opencast_series
	 */
	public function delete($ref_id, $delete_opencast_series) {
		$object = new ilObjOpenCast($ref_id);
		if ($delete_opencast_series) {
			xoctOpenCast::find($object->getId())->getSeries()->delete();
		}
		$object->delete();
	}


	/**
	 * possible data:
	 *
	 *  title => text
	 *  permission_template_id => integer
	 *  description => text
	 *  online => boolean
	 *  introduction_text => text
	 *  license => text
	 *  use_annotations => boolean
	 *  streaming_only => boolean
	 *  permission_per_clip => boolean
	 *  permission_allow_set_own => boolean
	 *  member_upload => boolean
	 *
	 * @param $ref_id
	 * @param $data
	 *
	 * @return xoctOpenCast
	 */
	public function update($ref_id, $data) {
		$object = new ilObjOpenCast($ref_id);
		/** @var xoctOpenCast $cast */
		$cast = xoctOpenCast::where(array('obj_id' => $object->getId()))->first();
		$series = $cast->getSeries();

		$update_ilias_data = $update_opencast_data = false;

		// ilias data
		foreach (array('online', 'introduction_text', 'license', 'use_annotations', 'streaming_only', 'permission_per_clip', 'permission_allow_set_own') as $field) {
			if (isset($data[$field])) {
				$setter = 'set' . str_replace('_', '', $field);
				$cast->$setter($data[$field]);
				$update_ilias_data = true;
			}
		}
		if ($update_ilias_data) {
			$cast->update();
		}

		// opencast data
		if (isset($data['permission_template_id']) ||
			($series->getPermissionTemplateId() && (isset($data['use_annotations']) || isset($data['streaming_only'])))) {
			$series_acls = $series->getAccessPolicies();
			xoctPermissionTemplate::removeAllTemplatesFromAcls($series_acls);
			/** @var xoctPermissionTemplate $xoctPermissionTemplate */
			$xoctPermissionTemplate = xoctPermissionTemplate::find($data['permission_template_id'] ? $data['permission_template_id'] : $series->getPermissionTemplateId());
			$xoctPermissionTemplate->addToAcls($series_acls, !$cast->getStreamingOnly(), $cast->getUseAnnotations());
			$series->setAccessPolicies($series_acls);
			$update_opencast_data = true;
		}

		foreach (array('title', 'description') as $field) {
			if (isset($data[$field])) {
				$setter = 'set' . str_replace('_', '', $field);
				$series->$setter($data[$field]);
				$update_opencast_data = true;
			}
		}

		if ($update_opencast_data) {
			$series->update();
		}

		//member upload
		if (isset($data['member_upload'])) {
			ilObjOpenCastAccess::activateMemberUpload($ref_id);
		}

		return $cast;
	}
}
