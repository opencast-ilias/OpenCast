<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\PermissionTemplate;

use ActiveRecord;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\ACL\ACLEntry;

/**
 * Class xoctPermissionTemplate
 * TODO: move to /src, add namespace, create repository
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PermissionTemplate extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_perm_template';

    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getConnectorContainerName(): string
    {
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
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role;

    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_name;

    /**
     * @var Integer
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role_read_access;
    /**
     * @var Integer
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role_write_access;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_acl_actions;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_actions_download;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_actions_annotate;

    public function create(): void
    {
        $this->setSort(self::count() + 1);
        parent::create();
    }

    public static function removeAllTemplatesFromAcls(ACL $ACL): ACL
    {
        if (empty($ACL->getEntries())) {
            return $ACL;
        }

        /** @var PermissionTemplate $perm_tpl */
        foreach (self::get() as $perm_tpl) {
            $ACL = $perm_tpl->removeFromAcls($ACL);
        }
        return $ACL;
    }

    /**
     * @return PermissionTemplate|bool
     */
    public static function getTemplateForAcls(ACL $ACL)
    {
        $acls_formatted = [];
        foreach ($ACL->getEntries() as $entry) {
            if (!isset($acls_formatted[$entry->getRole()])) {
                $acls_formatted[$entry->getRole()] = [];
            }
            $acls_formatted[$entry->getRole()][$entry->getAction()] = $entry->isAllow();
        }

        /** @var PermissionTemplate $perm_tpl */
        foreach (self::get() as $perm_tpl) {
            $entry = $acls_formatted[$perm_tpl->getRole()];
            if ($entry && (isset($entry[ACLEntry::READ]) === (bool) $perm_tpl->getRead(
                    )) && (isset($entry[ACLEntry::WRITE]) === (bool) $perm_tpl->getWrite())) {
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

    public function addToAcls(ACL $ACL, bool $with_download, bool $with_annotate): ACL
    {
        $this->removeFromAcls($ACL);
        return $ACL->merge($this->getAcls($with_download, $with_annotate));
    }

    public function removeFromAcls(ACL $ACL): ACL
    {
        $entries = $ACL->getEntries();
        foreach ($ACL->getEntries() as $key => $existing_acl) {
            if ($existing_acl->getRole() === $this->getRole() || $existing_acl->getRole() == $this->getAddedRoleName(
                )) {
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
     */
    public function getAcls($with_download, $with_annotate): ACL
    {
        $entries = [];

        if ($this->getRead() !== 0) {
            $entries[] = $this->constructAclForAction(ACLEntry::READ);
        }

        if ($this->getWrite() !== 0) {
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
        if ($this->getAddedRole()) {
            $role_name = $this->getAddedRoleName();
            if ($this->getAddedRoleRead()) {
                $entries[] = $this->constructAclActionForRole(ACLEntry::READ, $role_name);
            }

            if ($this->getAddedRoleWrite()) {
                $entries[] = $this->constructAclActionForRole(ACLEntry::WRITE, $role_name);
            }

            foreach (array_filter(explode(',', $this->getAddedRoleAclActions())) as $additional_action) {
                $entries[] = $this->constructAclActionForRole($additional_action, $role_name);
            }

            if ($with_download && $this->getAddedRoleActionsDownload()) {
                foreach (explode(',', $this->getAddedRoleActionsDownload()) as $additional_action) {
                    $entries[] = $this->constructAclActionForRole($additional_action, $role_name);
                }
            }

            if ($with_annotate && $this->getAddedRoleActionsAnnotate()) {
                foreach (explode(',', $this->getAddedRoleActionsAnnotate()) as $additional_action) {
                    $entries[] = $this->constructAclActionForRole($additional_action, $role_name);
                }
            }
        }

        return new ACL($entries);
    }

    protected function constructAclForAction($action): ACLEntry
    {
        return new ACLEntry($this->getRole(), $action, true);
    }

    protected function constructAclActionForRole($action, $role): ACLEntry
    {
        return new ACLEntry($role, $action, true);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return String
     */
    public function getTitle()
    {
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
    public function setTitleDE($title_de): void
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
    public function setTitleEN($title_en): void
    {
        $this->title_en = $title_en;
    }

    /**
     * @return String
     */
    public function getInfo()
    {
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
    public function setInfoDE($info_de): void
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
    public function setInfoEN($info_en): void
    {
        $this->info_en = $info_en;
    }

    /**
     * @return String
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param String $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getRead()
    {
        return $this->read_access;
    }

    /**
     * @param int $read
     */
    public function setRead($read): void
    {
        $this->read_access = $read;
    }

    /**
     * @return int
     */
    public function getWrite()
    {
        return $this->write_access;
    }

    /**
     * @param int $write
     */
    public function setWrite($write): void
    {
        $this->write_access = $write;
    }

    public function getAdditionalAclActions(): string
    {
        return str_replace(' ', '', $this->additional_acl_actions);
    }

    /**
     * @param String $additional_acl_actions
     */
    public function setAdditionalAclActions($additional_acl_actions): void
    {
        $this->additional_acl_actions = $additional_acl_actions;
    }

    /**
     * @return String
     */
    public function getAdditionalActionsDownload()
    {
        return $this->additional_actions_download;
    }

    /**
     * @param String $additional_actions_download
     */
    public function setAdditionalActionsDownload($additional_actions_download): void
    {
        $this->additional_actions_download = $additional_actions_download;
    }

    /**
     * @return String
     */
    public function getAdditionalActionsAnnotate()
    {
        return $this->additional_actions_annotate;
    }

    /**
     * @param String $additional_actions_annotate
     */
    public function setAdditionalActionsAnnotate($additional_actions_annotate): void
    {
        $this->additional_actions_annotate = $additional_actions_annotate;
    }

    public function getAddedRole(): ?string
    {
        return $this->added_role;
    }

    public function setAddedRole(?string $added_role): void
    {
        $this->added_role = $added_role;
    }

    public function getAddedRoleName(): ?string
    {
        return $this->added_role_name;
    }

    public function setAddedRoleName(?string $added_role_name): void
    {
        $this->added_role_name = $added_role_name;
    }

    public function getAddedRoleRead(): ?int
    {
        return $this->added_role_read_access;
    }

    public function setAddedRoleRead(?int $read): void
    {
        $this->added_role_read_access = $read;
    }

    public function getAddedRoleWrite(): ?int
    {
        return $this->added_role_write_access;
    }

    public function setAddedRoleWrite(?int $write): void
    {
        $this->added_role_write_access = $write;
    }

    public function getAddedRoleAclActions(): ?string
    {
        return str_replace(' ', '', $this->added_role_acl_actions);
    }

    public function setAddedRoleAclActions(?string $additional_acl_actions): void
    {
        $this->added_role_acl_actions = $additional_acl_actions;
    }

    public function getAddedRoleActionsDownload(): ?string
    {
        return $this->added_role_actions_download;
    }

    public function setAddedRoleActionsDownload(?string $additional_actions_download): void
    {
        $this->added_role_actions_download = $additional_actions_download;
    }

    public function getAddedRoleActionsAnnotate(): ?string
    {
        return $this->added_role_actions_annotate;
    }

    public function setAddedRoleActionsAnnotate(?string $additional_actions_annotate): void
    {
        $this->added_role_actions_annotate = $additional_actions_annotate;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort($sort): void
    {
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
    public function setDefault($default): void
    {
        $this->is_default = $default;
    }
}
