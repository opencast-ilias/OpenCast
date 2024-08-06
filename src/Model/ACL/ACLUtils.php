<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\ACL;

use ilObjUser;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\User\xoctUser;

class ACLUtils
{
    /**
     * A combination of standard roles, user roles and owner roles. Used on newly created objects.
     */
    public function getBaseACLForUser(xoctUser $user): ACL
    {
        return $this->getOwnerRolesACL($user)
                    ->merge($this->getUserRolesACL($user))
                    ->merge($this->getStandardRolesACL());
    }

    public function getStandardRolesACL(): ACL
    {
        $entries = [];
        foreach (PluginConfig::getConfig(PluginConfig::F_STD_ROLES) as $std_role) {
            if (!$std_role) {
                continue;
            }
            $entries[] = new ACLEntry($std_role, ACLEntry::READ, true);
            $entries[] = new ACLEntry($std_role, ACLEntry::WRITE, true);
        }
        return new ACL($entries);
    }

    public function getUserRolesACL(xoctUser $user): ACL
    {
        if ($user->getUserRoleName() === null) {
            return new ACL([]);
        }
        $acl_list = new ACL([
            new ACLEntry($user->getUserRoleName(), ACLEntry::WRITE, true),
            new ACLEntry($user->getUserRoleName(), ACLEntry::READ, true)
        ]);

        $additional_actions = PluginConfig::getConfig(PluginConfig::F_ROLE_USER_ACTIONS);
        if ($additional_actions) {
            foreach ($additional_actions as $action) {
                if ($action != "") {
                    $acl_list->add(new ACLEntry($user->getUserRoleName(), $action, true));
                }
            }
        }
        return $acl_list;
    }

    public function getOwnerRolesACL(xoctUser $user): ACL
    {
        if (!$user->getOwnerRoleName()) {
            return new ACL();
        }
        return new ACL(
            [
                new ACLEntry($user->getOwnerRoleName(), ACLEntry::READ, true),
                new ACLEntry($user->getOwnerRoleName(), ACLEntry::WRITE, true)
            ]
        );
    }

    public function getOwnerOfEvent(Event $event): ?xoctUser
    {
        $acl = $this->getOwnerAclOfEvent($event);
        $entries = $acl->getEntries();
        if ($entries !== []) {
            $first_entry = array_shift($entries);
            $usr_id = xoctUser::lookupUserIdForOwnerRole($first_entry->getRole());
            if ($usr_id !== 0 && $usr_id !== null) {
                return xoctUser::getInstance(new ilObjUser($usr_id));
            }
        }
        return null;
    }

    public function getOwnerUsernameOfEvent(Event $event)
    {
        $owner = $this->getOwnerOfEvent($event);
        if (!$owner instanceof xoctUser) {
            return $event->getMetadata()->getField('rightsHolder')->getValue() ?: '&nbsp';
        }
        return $owner->getNamePresentation();
    }

    public function removeOwnerFromACL(ACL $acl): ACL
    {
        $standard_roles = PluginConfig::getConfig(PluginConfig::F_STD_ROLES);
        $acl_entries = $acl->getEntries();
        foreach ($acl_entries as $i => $acl_entry) {
            if (!str_contains(
                $acl_entry->getRole(),
                (string) str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())
            )) {
                continue;
            }
            if (in_array($acl_entry->getRole(), $standard_roles)) {
                continue;
            }
            unset($acl_entries[$i]);
        }
        return new ACL($acl_entries);
    }

    public function getOwnerAclOfEvent(Event $event): ACL
    {
        return new ACL(
            array_filter($event->getAcl()->getEntries(), fn(ACLEntry $entry): bool => $this->isOwnerRole($entry))
        );
    }

    public function changeOwner(ACL $ACL, xoctUser $owner): ACL
    {
        return $this->removeOwnerFromACL($ACL)
                    ->merge($this->getOwnerRolesACL($owner));
    }

    public function isOwnerRole(ACLEntry $ACLEntry): bool
    {
        return str_contains(
            $ACLEntry->getRole(),
            (string) str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())
        );
    }

    public function isUserOwnerOfEvent(xoctUser $user, Event $event): bool
    {
        $owner = $this->getOwnerOfEvent($event);
        return !is_null($owner) && ($owner->getIliasUserId() === $user->getIliasUserId());
    }
}
