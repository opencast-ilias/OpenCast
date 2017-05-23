<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/class.xoctMigrationLog.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoct.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilObjScast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Group/class.xscaGroup.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctGroup.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctIVTGroup.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctIVTGroupParticipant.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Invitations/class.xoctInvitation.php';
/**
 * Class xoctScaMigration
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctScaMigration {

	const EVENTS = 'events';
	const SERIES = 'series';
	const EVENT_ID_OLD = 'ext_id';
	const EVENT_ID_NEW = 'cast2_event_id';
	const SERIES_ID_OLD = 'channel_ext_id';
	const SERIES_ID_NEW = 'cast2_series_id';
	const ALLOW_ANNOTATIONS = 'channel_allow_annotations';
	const STREAMING_ONLY = 'channel_streaming_only';


	/**
	 * @var array
	 */
	protected $id_mapping = array(
		"series" => array(),
		"events" => array()
	);
	/**
	 * @var Integer
	 */
	protected $ops_id_write;
	/**
	 * @var Integer
	 */
	protected $ops_id_edit_videos;
	/**
	 * @var Integer
	 */
	protected $ops_id_upload;
	/**
	 * @var array
	 */
	protected $channel_config = array();
	/**
	 * @var null
	 */
	protected $migration_data;
	/**
	 * @var xoctMigrationLog
	 */
	protected $log;
	/**
	 * @var ilDB|ilDBInnoDB|ilDBMySQL|ilDBOracle|ilDBPostgreSQL
	 */
	protected $db;
	/**
	 * @var ilRbacAdmin
	 */
	protected $rbac_admin;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac_review;
	/**
	 * @var int
	 */
	protected $migrated_count = 0;
	/**
	 * @var int
	 */
	protected $skipped_count = 0;
	/**
	 * @var bool
	 */
	protected $command_line;

	/**
	 * xoctScaMigration constructor.
	 */
	public function __construct($migration_data, $command_line_execution = false) {
		global $ilDB, $rbacadmin, $rbacreview;
		$this->migration_data = $migration_data;
		$this->log = xoctMigrationLog::getInstance();
		$this->db = $ilDB;
		$this->rbac_admin = $rbacadmin;
		$this->rbac_review = $rbacreview;
		$this->command_line = $command_line_execution;
	}


	public function run() {
		$this->log->write('***Migration Start***', null, $this->command_line);
		if ($this->migration_data) {
			$this->createMapping($this->migration_data);
		} else {
			throw new ilException('Migration failed: no migration data given');
		}

		$this->initRoles();
//		$this->migratePermissions();
		$this->migrateObjectData();
		$this->migrateInvitations();
		$this->log->write('***Migration Succeeded***', null, $this->command_line);
		return array('migrated' => $this->migrated_count, 'skipped' => $this->skipped_count);
	}

	protected function initRoles() {
		$query = $this->db->query("SELECT ops_id FROM rbac_operations WHERE operation = 'write'");
		while ($rec = $this->db->fetchAssoc($query)) {
			$this->ops_id_write = $rec['ops_id'];
		}
		$query = $this->db->query("SELECT ops_id FROM rbac_operations WHERE operation = 'rep_robj_xoct_perm_edit_videos'");
		while ($rec = $this->db->fetchAssoc($query)) {
			$this->ops_id_edit_videos = $rec['ops_id'];
		}
		$query = $this->db->query("SELECT ops_id FROM rbac_operations WHERE operation = 'rep_robj_xoct_perm_upload'");
		while ($rec = $this->db->fetchAssoc($query)) {
			$this->ops_id_upload = $rec['ops_id'];
		}
		if (!$this->ops_id_write || !$this->ops_id_edit_videos || !$this->ops_id_upload) {
			throw new ilException('Migration failed: rbac operation id(s) not found!');
		}
	}

	protected function createMapping($migration_data) {
		if (!is_array($migration_data)) {
			$mapping = json_decode($migration_data, true);
			if (!is_array($mapping)) {
				throw new ilException('Mapping of ids failed: Format of migration data invalid');
			}
		}

		if (!$clips = $mapping['clips']) {
			throw new ilException('Mapping of ids failed: field "clips" not found');
		}

		// iterate clips and create mapping
		foreach ($clips as $clip) {
			$this->id_mapping[self::EVENTS][$clip[self::EVENT_ID_OLD]] = $clip[self::EVENT_ID_NEW];
			$this->id_mapping[self::SERIES][$clip[self::SERIES_ID_OLD]] = $clip[self::SERIES_ID_NEW];
			$this->channel_config[$clip[self::SERIES_ID_NEW]][self::STREAMING_ONLY] = ($clip[self::STREAMING_ONLY] == 'yes');
			$this->channel_config[$clip[self::SERIES_ID_NEW]][self::ALLOW_ANNOTATIONS] = ($clip[self::ALLOW_ANNOTATIONS] == 'yes');
		}
	}

	protected function migrateObjectData() {
		global $tree;
		$this->log->write('migrate Object Data..', null, $this->command_line);
		$sql = $this->db->query('SELECT rep_robj_xsca_data.*, object_reference.ref_id FROM rep_robj_xsca_data INNER JOIN object_reference on rep_robj_xsca_data.id = object_reference.obj_id');
		if ($this->command_line) {
			echo "Processed: $this->migrated_count, Skipped: $this->skipped_count \r";
		}
		while ($rec = $this->db->fetchAssoc($sql)) {
//			if ($rec['ref_id'] != 1320646) {
//				continue;
//			}
			$ilObjSCast = new ilObjScast($rec['ref_id']);
			$series_id = $this->id_mapping[self::SERIES][$rec['ext_id']];

			if (!$series_id) {
				$this->log->write("WARNING: no mapping found for channel_id {$rec['ext_id']}");
				$this->log->write("skip and proceed with next object");
				$this->skipped_count++;
				continue;
			}

			$parent_id = $tree->getParentId($ilObjSCast->getRefId());
			if (!$parent_id) {
				$this->log->write("WARNING: no parent id found for ref_id {$rec['ref_id']}");
				$this->log->write("skip and proceed with next object");
				$this->skipped_count++;
				continue;
			}
			$this->log->write("create ilObjOpenCast..");
			$this->log->write("migrating scast: title={$ilObjSCast->getTitle()} ref_id={$ilObjSCast->getRefId()} obj_id={$rec['id']} channel_id={$rec['ext_id']} parent_id=$parent_id");
			$ilObjOpenCast = new ilObjOpenCast();
			$ilObjOpenCast->setTitle($ilObjSCast->getTitle());
			$ilObjOpenCast->setDescription($ilObjSCast->getDescription());
			$ilObjOpenCast->setOwner($ilObjSCast->getOwner());
			$ilObjOpenCast->create();
			$ilObjOpenCast->createReference();

			$this->log->write("putInTree..");
			$ilObjOpenCast->putInTree($parent_id);
			$ilObjOpenCast->setPermissions($parent_id);


			$this->log->write("create xoctOpenCast..");
			$cast = new xoctOpenCast();
			$cast->setObjId($ilObjOpenCast->getId());
			$cast->setSeriesIdentifier($series_id);
			$cast->create();

			$cast->setObjOnline($ilObjSCast->getOnline());
			$cast->setPermissionPerClip($ilObjSCast->getIvt());
			$cast->setPermissionAllowSetOwn($ilObjSCast->getInvitingPossible());
			$cast->setIntroText($ilObjSCast->getIntroductionText());
			$cast->setUseAnnotations($this->channel_config[$series_id][self::ALLOW_ANNOTATIONS]);
			$cast->setStreamingOnly($this->channel_config[$series_id][self::STREAMING_ONLY]);
			$cast->update();

			// add producers
			$producers = $ilObjSCast->getProducers();
			$cast->getSeries()->addProducers($producers);
			try {
				$ilias_producers = xoctGroup::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
				$ilias_producers->addMembers($producers);
			} catch (xoctException $e) {
				$this->log->write('WARNING: ' . $e->getMessage());
			}


			$this->log->write("opencast creation succeeded: ref_id={$ilObjOpenCast->getRefId()} obj_id={$ilObjOpenCast->getId()} series_id={$cast->getSeriesIdentifier()}");
			$this->migrated_count++;
			$this->migrateGroups($ilObjSCast->getId(), $ilObjOpenCast->getId());

			if ($this->command_line) {
				echo "Processed: $this->migrated_count, Skipped: $this->skipped_count \r";
			}

			//permissions
			$parent_obj = $ilObjOpenCast->getParentCourseOrGroup();
			$roles = ($parent_obj instanceof ilObjCourse) ? $parent_obj->getDefaultCourseRoles() : $parent_obj->getDefaultGroupRoles();

			foreach ($roles as $role_id) {
				$role_ops = $this->rbac_review->getRoleOperationsOnObject($role_id, $rec['ref_id']);

				// if the role has write permissions, the new permissions 'edit_videos' and 'upload' are also granted
				if (in_array($this->ops_id_write, $role_ops)) {
					$role_ops[] = $this->ops_id_edit_videos;
					$role_ops[] = $this->ops_id_upload;
				}

				$this->rbac_admin->revokePermission($ilObjOpenCast->getRefId(), $role_id);
				$this->rbac_admin->grantPermission($role_id, $role_ops, $ilObjOpenCast->getRefId());
			}


		}
		echo "\n";
		$this->log->write('Migration of Object Data Succeeded', null, $this->command_line);
	}

	protected function migrateGroups($sca_id, $xoct_id) {
		$this->log->write('migrate groups..');
		foreach (xscaGroup::getAllForObjId($sca_id) as $sca_group) {
			$this->log->write("creating group {$sca_group->getTitle()}..");
			$xoct_group = new xoctIVTGroup();
			$xoct_group->setSerieId($xoct_id);
			$xoct_group->setTitle($sca_group->getTitle());
			$xoct_group->create();
			foreach ($sca_group->getMemberIds() as $member_id) {
				$this->log->write("adding group member $member_id..");
				$xoct_group_participant = new xoctIVTGroupParticipant();
				$xoct_group_participant->setUserId($member_id);
				$xoct_group_participant->setGroupId($xoct_group->getId());
				$xoct_group_participant->create();
			}
		}
		$this->log->write("migration of groups succeeded");
	}

	protected function migrateInvitations() {
		$this->log->write('migrate invitations..', null, $this->command_line);
		$sql = $this->db->query('SELECT * FROM rep_robj_xsca_cmember');
		$skipped = 0;
		$migrated = 0;
		while ($rec = $this->db->fetchAssoc($sql)) {
			if ($this->command_line) {
				echo "Processed: $migrated, Skipped: $skipped \r";
			}
			$event_id = $this->id_mapping[self::EVENTS][$rec['clip_ext_id']];
			if (!$event_id) {
				$this->log->write("WARNING: no mapping found for clip_id {$rec['clip_ext_id']}");
				$this->log->write("skip and proceed with next invitation");
				$skipped++;
				continue;
			}
			$this->log->write("creating invitation for user {$rec['user_id']} and event $event_id");
			$invitation = new xoctInvitation();
			$invitation->setEventIdentifier($event_id);
			$invitation->setUserId($rec['user_id']);
			$invitation->setOwnerId(0);
			$invitation->create();
			$migrated++;
		}
		if ($this->command_line) {
			echo "\n";
		}
		$this->log->write('migration of invitations succeeded', null, $this->command_line);
	}

	protected function migratePermissions() {
		$this->log->write('migrate permission templates..', null, $this->command_line);
		$sql = $this->db->query('SELECT * FROM rbac_templates WHERE type = ' . $this->db->quote('xsca', 'text'));
		while ($rec = $this->db->fetchAssoc($sql)) {
			$this->db->insert('rbac_templates', array(
				'rol_id' => array('integer', $rec['rol_id']),
				'type' => array('text', 'xoct'),
				'ops_id' => array('integer', $rec['ops_id']),
				'parent' => array('integer', $rec['parent']),
			));
		}
		$this->log->write('migration of permission templates succeeded', null, $this->command_line);
	}
}