<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/class.xoctMigrationLog.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoct.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilObjScast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php';
require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Group/class.xscaGroup.php';
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


	/**
	 * @var array
	 */
	protected $id_mapping = array(
		"series" => array(),
		"events" => array()
	);
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

	protected $migrated_count = 0;

	protected $skipped_count = 0;


	/**
	 * xoctScaMigration constructor.
	 */
	public function __construct($migration_data) {
		global $ilDB;
		xoct::initILIAS();
		$this->migration_data = $migration_data;
		$this->log = xoctMigrationLog::getInstance();
		$this->db = $ilDB;
	}


	public function run() {
		$this->log->write('***Migration start***');
		if ($this->migration_data) {
			$this->createMapping($this->migration_data);
		} else {
			throw new ilException('Migration failed: no migration data given');
		}

		$this->migrateObjectData();
		$this->migrateInvitations();
		$this->log->write('***Migration Succeeded***');
		return array('migrated' => $this->migrated_count, 'skipped' => $this->skipped_count);
		//TODO config?
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
		}
	}

	protected function migrateObjectData() {
		global $tree;
		$this->log->write('*Migrate Object Data*');
		$sql = $this->db->query('SELECT rep_robj_xsca_data.*, object_reference.ref_id FROM rep_robj_xsca_data INNER JOIN object_reference on rep_robj_xsca_data.id = object_reference.obj_id');
		while ($rec = $this->db->fetchAssoc($sql)) {
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
			$cast->setUseAnnotations($ilObjSCast->getAllowAnnotations());
			$cast->setStreamingOnly($ilObjSCast->getStreamingOnly());
			$cast->update();

			// add producers
			$producers = array();
			if ($crs_or_grp_obj = ilObjOpenCast::getParentCourseOrGroup($ilObjOpenCast->getRefId())) {

				//check each role (admin,tutor,member) for perm edit_videos, add to series and producer group
				foreach (array('admin', 'tutor') as $role) {
					if (ilObjOpenCastAccess::isActionAllowedForRole('edit_videos', $role, $ilObjOpenCast->getRefId())) {
						$getter_method = "get{$role}s";
						foreach ($crs_or_grp_obj->getMembersObject()->$getter_method() as $participant_id) {
							$producers[] = xoctUser::getInstance($participant_id);
						}
					}
				}
			}
			if (!empty($producers)) {
				$cast->getSeries()->addProducers($producers);
				try {
					$ilias_producers = xoctGroup::find(xoctConf::get(xoctConf::F_GROUP_PRODUCERS));
					$ilias_producers->addMembers($producers);
				} catch (xoctException $e) {
					$this->log->write('WARNING: ' . $e->getMessage());
				}
			}


			$this->log->write("opencast creation succeeded: ref_id={$ilObjOpenCast->getRefId()} obj_id={$ilObjOpenCast->getId()} series_id={$cast->getSeriesIdentifier()}");
			$this->migrated_count++;
			$this->migrateGroups($ilObjSCast->getId(), $ilObjOpenCast->getId());


		}
		$this->log->write('Migration of Object Data Succeeded');
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
		$this->log->write('migrate invitations..');
		$sql = $this->db->query('SELECT * FROM rep_robj_xsca_cmember');
		while ($rec = $this->db->fetchAssoc($sql)) {
			$event_id = $this->id_mapping[self::EVENTS][$rec['clip_ext_id']];
			if (!$event_id) {
				$this->log->write("WARNING: no mapping found for clip_id {$rec['clip_ext_id']}");
				$this->log->write("skip and proceed with next invitation");
				continue;
			}
			$this->log->write("creating invitation for user {$rec['user_id']} and event $event_id");
			$invitation = new xoctInvitation();
			$invitation->setEventIdentifier($event_id);
			$invitation->setUserId($rec['user_id']);
			$invitation->setOwnerId(0);
			$invitation->create();
		}
		$this->log->write('migration of invitations succeeded');
	}
}