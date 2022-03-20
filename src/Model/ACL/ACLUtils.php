<?php

namespace srag\Plugins\Opencast\Model\ACL;

use ilObjUser;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\User\xoctUser;

class ACLUtils
{

    /**
     * A combination of standard roles, user roles and owner roles. Used on newly created objects.
     *
     * @param xoctUser $user
     * @return ACL
     */
    public function getBaseACLForUser(xoctUser $user) : ACL
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
        if($user->getUserRoleName() === null) {
           return new ACL([]);
        }

        return new ACL([
            new ACLEntry($user->getUserRoleName(), ACLEntry::WRITE, true),
            new ACLEntry($user->getUserRoleName(), ACLEntry::READ, true)]);
    }

    public function getOwnerRolesACL(xoctUser $user): ACL
    {
        if (!$user->getOwnerRoleName()) {
            return new ACL();
        }
        return new ACL(
            [new ACLEntry($user->getOwnerRoleName(), ACLEntry::READ, true),
                new ACLEntry($user->getOwnerRoleName(), ACLEntry::WRITE, true)]);
    }

    public function getOwnerOfEvent(Event $event): ?xoctUser
    {
        $acl = $this->getOwnerAclOfEvent($event);
        $entries = $acl->getEntries();
        if (!empty($entries)) {
            $first_entry = array_shift($entries);
            $usr_id = xoctUser::lookupUserIdForOwnerRole($first_entry->getRole());
            if ($usr_id) {
                return xoctUser::getInstance(new ilObjUser($usr_id));
            }
        }
        return null;
    }

    public function getOwnerUsernameOfEvent(Event $event)
    {
        $owner = $this->getOwnerOfEvent($event);
        if (!$owner) {
            return $event->getMetadata()->getField('rightsHolder')->getValue() ?: '&nbsp';
        }
        return $owner->getNamePresentation();
    }

    public function removeOwnerFromACL(ACL $acl): ACL
    {
        $standard_roles = PluginConfig::getConfig(PluginConfig::F_STD_ROLES);
        $ACLEntries = $acl->getEntries();
        foreach ($ACLEntries as $i => $acl) {
            if ((strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false)
                && !in_array($acl->getRole(), $standard_roles)) {
                unset($ACLEntries[$i]);
            }
        }
        return new ACL($ACLEntries);
    }

    public function getOwnerAclOfEvent(Event $event): ACL
    {
        return new ACL(array_filter($event->getAcl()->getEntries(), function(ACLEntry $entry) {
            return $this->isOwnerRole($entry);
        }));
    }

    public function changeOwner(ACL $ACL, xoctUser $owner) : ACL
    {
        return $this->removeOwnerFromACL($ACL)
            ->merge($this->getOwnerRolesACL($owner));
    }

    public function isOwnerRole(ACLEntry $ACLEntry) : bool
    {
        return strpos($ACLEntry->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false;
    }

    public function isUserOwnerOfEvent(xoctUser $user, Event $event) : bool
    {
        $owner = $this->getOwnerOfEvent($event);
        return !is_null($owner) && ($owner->getIliasUserId() == $user->getIliasUserId());
    }
}