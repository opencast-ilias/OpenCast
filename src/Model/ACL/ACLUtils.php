<?php

namespace srag\Plugins\Opencast\Model\ACL;

use ACLEntry;
use ilObjUser;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctConf;
use xoctUser;

class ACLUtils
{

    public function getOwner(Event $event) : ?xoctUser
    {
        $acl = $this->getOwnerAcl($event);
        if ($acl instanceof ACLEntry) {
            $usr_id = xoctUser::lookupUserIdForOwnerRole($acl->getRole());
            if ($usr_id) {
                return xoctUser::getInstance(new ilObjUser($usr_id));
            }
        }
        return null;
    }

    public function setOwner(xoctUser $user, Event $event) : Event
    {
        $event->getMetadata()->getField(MDFieldDefinition::F_RIGHTS_HOLDER)->setValue($user->getNamePresentation());

        if (!$user->getOwnerRoleName()) {
            return $event;
        }

        $event = $this->removeAllOwnerAcls($event);
        $event->getAcl()->add(new ACLEntry($user->getOwnerRoleName(), ACLEntry::READ, true));
        $event->getAcl()->add(new ACLEntry($user->getOwnerRoleName(), ACLEntry::WRITE, true));
        return $event;
    }

    public function removeOwner(Event $event) : Event
    {
        $event = $this->removeAllOwnerAcls($event);
        $event->getMetadata()->getField(MDFieldDefinition::F_RIGHTS_HOLDER)->setValue('');
        return $event;
    }

    public function removeAllOwnerAcls(Event $event) : Event
    {
        $standard_roles = xoctConf::getConfig(xoctConf::F_STD_ROLES);
        $ACLEntries = $event->getAcl()->getEntries();
        foreach ($ACLEntries as $i => $acl) {
            if ((strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false)
                && !in_array($acl->getRole(), $standard_roles)) {
                unset($ACLEntries[$i]);
            }
        }
        $event->getAcl()->setEntries($ACLEntries);
        return $event;
    }

    public function getOwnerAcl(Event $event) : ?ACLEntry
    {
        static $owner_acl;
        if (isset($owner_acl[$event->getIdentifier()])) {
            return $owner_acl[$event->getIdentifier()];
        }
        foreach ($event->getAcl()->getEntries() as $acl_entry) {
            if (strpos($acl_entry->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false) {
                $owner_acl[$event->getIdentifier()] = $acl_entry;

                return $acl_entry;
            }
        }
        $owner_acl[$event->getIdentifier()] = null;

        return null;
    }
}