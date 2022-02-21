<?php

use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Group\Group;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequestPayload;
use srag\Plugins\Opencast\Model\Series\SeriesRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Util\DI\OpencastDIC;

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
     * @var SeriesRepository
     */
    private $series_repository;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $seriesWorkflowParameterRepository;
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;
    /**
     * @var ACLUtils
     */
    private $aclUtils;


    /**
	 * SeriesAPI constructor.
	 */
	public function __construct() {
        $opencastDIC = OpencastDIC::getInstance();
        $this->series_repository = $opencastDIC->series_repository();
        $this->seriesWorkflowParameterRepository = $opencastDIC->workflow_parameter_series_repository();
        $this->metadataFactory = $opencastDIC->metadata()->metadataFactory();
        $this->aclUtils = $opencastDIC->acl_utils();
	}


	/**
	 * @return xoctSeriesAPI
	 */
	public static function getInstance() : self
    {
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
     *  description => text
     *  online => boolean
     *  introduction_text => text
     *  license => text
     *  use_annotations => boolean
     *  streaming_only => boolean
     *  permission_per_clip => boolean
     *  permission_allow_set_own => boolean
     *  member_upload => boolean
     *  producers => int[]
     *
     *
     * @param int $parent_ref_id
     * @param string $title
     * @param array $additional_data
     *
     * @return ObjectSettings
     * @throws xoctException
     * @throws xoctInternalApiException
     */
	public function create(int $parent_ref_id, string $title, $additional_data = array()) : ObjectSettings
    {
		if (!ilObjOpenCast::_getParentCourseOrGroup($parent_ref_id)) {
			throw new xoctInternalApiException("object with parent_ref_id $parent_ref_id is not a course/group or inside a course/group");
		}

        $ilObjOpenCast = new ilObjOpenCast();
        if (isset($additional_data['owner'])) {
            $ilObjOpenCast->setOwner($additional_data['owner']);
        }
        $ilObjOpenCast->setTitle($title);
        $ilObjOpenCast->setDescription($additional_data['description'] ?? '');
        $ilObjOpenCast->create();
        $ilObjOpenCast->createReference();
        $ilObjOpenCast->putInTree($parent_ref_id);
        $ilObjOpenCast->setPermissions($parent_ref_id);

		$objectSettings = new ObjectSettings();
		$objectSettings->setOnline($additional_data['online'] ?? false);
		$objectSettings->setAgreementAccepted(true);
		$objectSettings->setIntroductionText($additional_data['introduction_text'] ?? '');
		$objectSettings->setUseAnnotations($additional_data['use_annotations'] ?? false);
		$objectSettings->setStreamingOnly($additional_data['streaming_only'] ?? false);
		$objectSettings->setPermissionPerClip($additional_data['permission_per_clip'] ?? false);
		$objectSettings->setPermissionAllowSetOwn($additional_data['permission_allow_set_own'] ?? false);

        $metadata = $this->metadataFactory->series();
        $metadata->getField(MDFieldDefinition::F_TITLE)->setValue($title);
        $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->setValue($additional_data['description'] ?? '');
        $metadata->getField(MDFieldDefinition::F_LICENSE)->setValue($additional_data['license'] ?? '');

        $acl = $this->aclUtils->getStandardRolesACL();
		if (isset($additional_data['permission_template_id'])) {
			PermissionTemplate::removeAllTemplatesFromAcls($acl);
			/** @var PermissionTemplate $xoctPermissionTemplate */
			$xoctPermissionTemplate = PermissionTemplate::find($additional_data['permission_template_id']);
			$xoctPermissionTemplate->addToAcls($acl, !$objectSettings->getStreamingOnly(), $objectSettings->getUseAnnotations());
		} elseif ($default_template = PermissionTemplate::where(array('is_default' => 1))->first()) {
            /** @var PermissionTemplate $default_template */
            $default_template->addToAcls($acl, !$objectSettings->getStreamingOnly(), $objectSettings->getUseAnnotations());
        }

		// add producers
        $producers = ilObjOpenCastAccess::getProducersForRefID($ilObjOpenCast->getRefId());

        if (isset($additional_data['owner'])) {
            $producers[] = xoctUser::getInstance($additional_data['owner']);
        }

        if (is_array($additional_data['producers'])) {
            foreach ($additional_data['producers'] as $producer) {
                $producers[] = xoctUser::getInstance($producer);
            }
        }

        try {
            $ilias_producers = Group::find(PluginConfig::getConfig(PluginConfig::F_GROUP_PRODUCERS));
            $ilias_producers->addMembers($producers);
        } catch (xoctException $e) {
        }

        foreach ($producers as $producer) {
            $acl->merge($this->aclUtils->getUserRolesACL($producer));
        }

//        $series->addOrganizer(ilObjOpencast::_getParentCourseOrGroup($ilObjOpenCast->getRefId())->getTitle(), true);

		$series_id = $this->series_repository->create(new CreateSeriesRequest(new CreateSeriesRequestPayload($metadata, $acl)));

		$objectSettings->setSeriesIdentifier($series_id);
        $objectSettings->setObjId($ilObjOpenCast->getId());
        $objectSettings->create();

		//member upload
		if (isset($additional_data['member_upload'])) {
			ilObjOpenCastAccess::activateMemberUpload($ilObjOpenCast->getRefId());
		}

		$this->seriesWorkflowParameterRepository->syncAvailableParameters($ilObjOpenCast->getId());

		return $objectSettings;
	}


	/**
	 * @param $ref_id
	 *
	 * @return ObjectSettings
	 */
	public function read($ref_id) {
		/** @var ObjectSettings $cast */
		$cast = ObjectSettings::find(ilObjOpenCast::_lookupObjectId($ref_id));
		return $cast;
	}


	/**
	 * @param      $ref_id
	 * @param bool $delete_opencast_series
	 */
	public function delete($ref_id, $delete_opencast_series) {
		$object = new ilObjOpenCast($ref_id);
		if ($delete_opencast_series) {
			ObjectSettings::find($object->getId())->getSeries()->delete();
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
	 * @return ObjectSettings
	 */
	public function update($ref_id, $data) {
		$object = new ilObjOpenCast($ref_id);
		/** @var ObjectSettings $cast */
		$cast = ObjectSettings::where(array('obj_id' => $object->getId()))->first();
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
			PermissionTemplate::removeAllTemplatesFromAcls($series_acls);
			/** @var PermissionTemplate $xoctPermissionTemplate */
			$xoctPermissionTemplate = PermissionTemplate::find($data['permission_template_id'] ? $data['permission_template_id'] : $series->getPermissionTemplateId());
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
