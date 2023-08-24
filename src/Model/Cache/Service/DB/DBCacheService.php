<?php

namespace srag\Plugins\Opencast\Model\Cache\Service\DB;

use ilDBInterface;
use ilGlobalCacheService;

/**
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class DBCacheService extends ilGlobalCacheService
{
    public const TYPE_DB = 99;

    protected function getActive(): bool
    {
        return true;
    }

    protected function getInstallable(): bool
    {
        return true;
    }

    public function unserialize($serialized_value)
    {
        return unserialize($serialized_value);
    }

    public function get($key)
    {
        /** @var DBCacheAR|null $record */
        $record = DBCacheAR::where(['identifier' => $key])->first();
        return is_null($record) || $this->isExpired($record) ? false : $record->getValue();
    }

    public function set($key, $serialized_value, $ttl = null): void
    {
        /** @var DBCacheAR|null $record */
        $record = DBCacheAR::where(['identifier' => $key])->first();
        if (is_null($record)) {
            $record = new DBCacheAR();
            $record->setIdentifier($key);
        }

        $record->setValue($serialized_value);
        $record->setExpires(is_null($ttl) ? $ttl : (time() + (int) $ttl));
        $record->store();
    }

    public function serialize($value)
    {
        try {
            return serialize($value);
        } catch (\Throwable $ex) {
            return false;
        }
    }

    public function exists($key)
    {
        return DBCacheAR::where(['identifier' => $key])->hasSets();
    }

    public function delete($key): bool
    {
        $record = DBCacheAR::where(['identifier' => $key])->first();
        if ($record instanceof DBCacheAR) {
            $record->delete();
            return true;
        }
        return false;
    }

    public function flush($complete = false): bool
    {
        DBCacheAR::truncateDB();
        return true;
    }

    protected function isExpired(DBCacheAR $DBCacheAR): bool
    {
        return !is_null($DBCacheAR->getExpires()) && (time() > $DBCacheAR->getExpires());
    }

    public static function cleanup(ilDBInterface $database): void
    {
        $database->query('DELETE FROM ' . DBCacheAR::TABLE_NAME . ' WHERE expires < ' . time());
    }
}
