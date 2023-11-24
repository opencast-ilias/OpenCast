<?php

declare(strict_types=1);

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

    public static function getTemplateForAcls(ACL $ACL): ?PermissionTemplate
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
            $entry = $acls_formatted[$perm_tpl->getRole()] ?? null;
            if ($entry === null) {
                continue;
            }
            $perm_read = (bool) $perm_tpl->getRead();
            $perm_write = (bool) $perm_tpl->getWrite();

            $entry_read = isset($entry[ACLEntry::READ]) && (bool) $entry[ACLEntry::READ];
            $entry_write = isset($entry[ACLEntry::WRITE]) && (bool) $entry[ACLEntry::WRITE];
            // This check has been removed, see https://github.com/opencast-ilias/OpenCast/issues/212 .
            // But I leave it here for now, since there must be some reason why it was there in the first place.

            // if (($perm_read === $entry_read) && ($perm_write === $entry_write)) {
            foreach (array_filter(explode(',', $perm_tpl->getAdditionalAclActions())) as $action) {
                if (!$entry[trim($action)]) {
                    continue 2;
                }
            }

            return $perm_tpl;
            // }
        }

        return null;
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
            if ($existing_acl->getRole() === $this->getRole()
                || $existing_acl->getRole() == $this->getAddedRoleName()) {
                unset($entries[$key]);
            }
        }
        $ACL->setEntries($entries);
        return $ACL;
    }


    public function getAcls(bool $with_download, bool $with_annotate): ACL
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

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        global $DIC;
        return $DIC->user()->getLanguage() === 'de' ? $this->title_de : $this->title_en;
    }

    public function getTitleDE(): string
    {
        return $this->title_de;
    }

    public function setTitleDE(string $title_de): void
    {
        $this->title_de = $title_de;
    }

    public function getTitleEN(): string
    {
        return $this->title_en;
    }

    public function setTitleEN(string $title_en): void
    {
        $this->title_en = $title_en;
    }

    public function getInfo(): string
    {
        global $DIC;
        return $DIC->user()->getLanguage() === 'de' ? $this->info_de : $this->info_en;
    }

    public function getInfoDE(): string
    {
        return $this->info_de;
    }

    public function setInfoDE(string $info_de): void
    {
        $this->info_de = $info_de;
    }

    public function getInfoEN(): string
    {
        return $this->info_en;
    }

    public function setInfoEN(string $info_en): void
    {
        $this->info_en = $info_en;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getRead(): int
    {
        return (int) $this->read_access;
    }

    public function setRead(int $read): void
    {
        $this->read_access = $read;
    }

    public function getWrite(): int
    {
        return (int) $this->write_access;
    }

    public function setWrite(int $write): void
    {
        $this->write_access = $write;
    }

    public function getAdditionalAclActions(): string
    {
        return str_replace(' ', '', $this->additional_acl_actions);
    }

    public function setAdditionalAclActions(string $additional_acl_actions): void
    {
        $this->additional_acl_actions = $additional_acl_actions;
    }

    public function getAdditionalActionsDownload(): string
    {
        return $this->additional_actions_download;
    }

    public function setAdditionalActionsDownload(string $additional_actions_download): void
    {
        $this->additional_actions_download = $additional_actions_download;
    }

    public function getAdditionalActionsAnnotate(): string
    {
        return $this->additional_actions_annotate;
    }

    public function setAdditionalActionsAnnotate(string $additional_actions_annotate): void
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

    public function getSort(): int
    {
        return (int) $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    public function setDefault(bool $default): void
    {
        $this->is_default = $default;
    }
}
