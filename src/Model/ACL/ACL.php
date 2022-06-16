<?php

namespace srag\Plugins\Opencast\Model\ACL;

use JsonSerializable;

class ACL implements JsonSerializable
{
    /**
     * @var ACLEntry[]
     */
    private $acl_entries;

    /**
     * @param ACLEntry[] $acl_entries
     */
    public function __construct(array $acl_entries = [])
    {
        $this->acl_entries = $acl_entries;
    }

    public static function fromResponse(array $response): self
    {
        $entries = [];
        foreach ($response as $data) {
            $entries[] = ACLEntry::fromArray((array) $data);
        }
        return new self($entries);
    }

    /**
     * @return ACLEntry[]
     */
    public function getEntries(): array
    {
        return $this->acl_entries;
    }

    /**
     * @param ACLEntry[] $acl_entries
     */
    public function setEntries(array $acl_entries): void
    {
        $this->acl_entries = $acl_entries;
    }

    public function add(ACLEntry $acl_entry): void
    {
        if (!in_array($acl_entry, $this->acl_entries, true)) {
            $this->acl_entries[] = $acl_entry;
        }
    }

    public function merge(ACL $acl): self
    {
        foreach ($acl->getEntries() as $entry) {
            $this->add($entry);
        }
        return $this;
    }

    public function jsonSerialize()
    {
        return array_values($this->getEntries());
    }
}
