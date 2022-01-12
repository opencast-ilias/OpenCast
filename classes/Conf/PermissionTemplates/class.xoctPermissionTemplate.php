<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\ACL\ACLEntry;

/**
 * Class xoctPermissionTemplate
 * TODO: move to /src, add namespace, create repository
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplate extends ActiveRecord {

	const TABLE_NAME = 'xoct_perm_template';


	/**
	 * @return string
	 * @deprecated
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @con_sequence        true
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $sort;
    /**
     * @var bool
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $is_default = 0;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title_de;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title_en;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $info_de;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $info_en;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $role;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $read_access;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $write_access;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_acl_actions;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_actions_download;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_actions_annotate;


	public function create() {
		$this->setSort(self::count() + 1);
		parent::create();
	}


	public static function removeAllTemplatesFromAcls(ACL $ACL) : ACL
    {
		if (empty($ACL->getEntries())) {
			return $ACL;
		}

		/** @var xoctPermissionTemplate $perm_tpl */
		foreach (self::get() as $perm_tpl) {
			$ACL = $perm_tpl->removeFromAcls($ACL);
		}
        return $ACL;
	}


	/**
	 * @param array $ACL
	 *
	 * @return xoctPermissionTemplate|bool
	 */
	public static function getTemplateForAcls(ACL $ACL) {
		$acls_formatted = array();
		foreach ($ACL->getEntries() as $entry) {
			if (!isset($acls_formatted[$entry->getRole()])) {
				$acls_formatted[$entry->getRole()] = array();
			}
			$acls_formatted[$entry->getRole()][$entry->getAction()] = $entry->isAllow();
		}

		/** @var xoctPermissionTemplate $perm_tpl */
		foreach (self::get() as $perm_tpl) {
			$entry = $acls_formatted[$perm_tpl->getRole()];
			if ($entry && (isset($entry[ACLEntry::READ]) == (bool)$perm_tpl->getRead()) && (isset($entry[ACLEntry::WRITE]) == (bool)$perm_tpl->getWrite())) {
				foreach (array_filter(explode(',', $perm_tpl->getAdditionalAclActions())) as $action) {
					if (!$entry[trim($action)]) {
						continue 2;
					}
				}

				return $perm_tpl;
			}
		}

		return false;
	}

    public function addToAcls(ACL $ACL, bool $with_download, bool $with_annotate) : ACL
    {
		$this->removeFromAcls($ACL);
		return $ACL->merge($this->getAcls($with_download, $with_annotate));
	}

	public function removeFromAcls(ACL $ACL) : ACL
    {
		/** @var ACLEntry $existing_acl */
        $entries = $ACL->getEntries();
        foreach ($ACL->getEntries() as $key => $existing_acl) {
			if ($existing_acl->getRole() == $this->getRole()) {
				unset($entries[$key]);
			}
		}
        $ACL->setEntries($entries);
        return $ACL;
	}


	/**
	 * @param $with_download
	 * @param $with_annotate
	 *
	 * @return array
	 */
	public function getAcls($with_download, $with_annotate) : ACL
    {
		$entries = array();

		if ($this->getRead()) {
			$entries[] = $this->constructAclForAction(ACLEntry::READ);
		}

		if ($this->getWrite()) {
			$entries[] = $this->constructAclForAction(ACLEntry::WRITE);
		}

		foreach (array_filter(explode(',', $this->getAdditionalAclActions())) as $additional_action) {
			$entries[] = $this->constructAclForAction($additional_action);
		}

		if ($with_download && $this->getAdditionalActionsDownload()) {
			foreach (explode(',', $this->getAdditionalActionsDownload()) as $additional_action) {
				$entries[] = $this->constructAclForAction($additional_action);
			}
		}

		if ($with_annotate && $this->getAdditionalActionsAnnotate()) {
			foreach (explode(',', $this->getAdditionalActionsAnnotate()) as $additional_action) {
				$entries[] = $this->constructAclForAction($additional_action);
			}
		}

		return new ACL($entries);
	}

	protected function constructAclForAction($action) : ACLEntry
    {
        return new ACLEntry($this->getRole(), $action, true);
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return String
	 */
	public function getTitle() {
	    global $DIC;
        return $DIC->user()->getLanguage() == 'de' ? $this->title_de : $this->title_en;
	}


    /**
     * @return String
     */
    public function getTitleDE()
    {
        return $this->title_de;
    }

    /**
     * @param String $title_de
     */
    public function setTitleDE($title_de)
    {
        $this->title_de = $title_de;
    }

    /**
     * @return String
     */
    public function getTitleEN()
    {
        return $this->title_en;
    }

    /**
     * @param String $title_en
     */
    public function setTitleEN($title_en)
    {
        $this->title_en = $title_en;
    }

	/**
	 * @return String
	 */
	public function getInfo() {
        global $DIC;
		return $DIC->user()->getLanguage() == 'de' ? $this->info_de : $this->info_en;
	}

    /**
     * @return String
     */
    public function getInfoDE()
    {
        return $this->info_de;
    }

    /**
     * @param String $info_de
     */
    public function setInfoDE($info_de)
    {
        $this->info_de = $info_de;
    }

    /**
     * @return String
     */
    public function getInfoEN()
    {
        return $this->info_en;
    }

    /**
     * @param String $info_en
     */
    public function setInfoEN($info_en)
    {
        $this->info_en = $info_en;
    }

	/**
	 * @return String
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param String $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}


	/**
	 * @return int
	 */
	public function getRead() {
		return $this->read_access;
	}


	/**
	 * @param int $read
	 */
	public function setRead($read) {
		$this->read_access = $read;
	}


	/**
	 * @return int
	 */
	public function getWrite() {
		return $this->write_access;
	}


	/**
	 * @param int $write
	 */
	public function setWrite($write) {
		$this->write_access = $write;
	}


	/**
	 * @return String
	 */
	public function getAdditionalAclActions() {
		return str_replace(' ', '', $this->additional_acl_actions);
	}


	/**
	 * @param String $additional_acl_actions
	 */
	public function setAdditionalAclActions($additional_acl_actions) {
		$this->additional_acl_actions = $additional_acl_actions;
	}


	/**
	 * @return String
	 */
	public function getAdditionalActionsDownload() {
		return $this->additional_actions_download;
	}


	/**
	 * @param String $additional_actions_download
	 */
	public function setAdditionalActionsDownload($additional_actions_download) {
		$this->additional_actions_download = $additional_actions_download;
	}


	/**
	 * @return String
	 */
	public function getAdditionalActionsAnnotate() {
		return $this->additional_actions_annotate;
	}


	/**
	 * @param String $additional_actions_annotate
	 */
	public function setAdditionalActionsAnnotate($additional_actions_annotate) {
		$this->additional_actions_annotate = $additional_actions_annotate;
	}


	/**
	 * @return int
	 */
	public function getSort() {
		return $this->sort;
	}


	/**
	 * @param int $sort
	 */
	public function setSort($sort) {
		$this->sort = $sort;
	}

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * @param int $default
     */
    public function setDefault($default)
    {
        $this->is_default = $default;
    }

}