<?php

namespace srag\Plugins\Opencast\TermsOfUse;


/**
 * class ToUManager
 * Manages the access to the AcceptedToU ActiveRecords
 *
 * @author fluxlabs <connect@fluxlabs.ch>
 * @author Sophie Pfister <sophie@fluxlabs.ch>
 */
class ToUManager
{
    // ToDo: Remove default value for instance
    protected static function create(int $user_id, int $instance_id = 0, bool $accepted = true)
    {
        $ar = new AcceptedToU();
        $ar->setUserId($user_id);
        $ar->setOCInstanceId($instance_id);
        if ($accepted) {
            $ar->setAccepted();
        } else {
            $ar->resetAccepted();
        }
        $ar->create();
    }

    // ToDo: Remove default value for instance
    public static function hasAcceptedToU(int $user_id, int $instance_id = 0) : bool
    {
        /** @var AcceptedToU $ar */
        if ($ar = AcceptedToU::where(["user_id" => $user_id, "oc_instance_id" => $instance_id])->first()) {
            return $ar->hasAccepted();
        }
        return false;
    }

    // ToDo: Remove default value for instance
    public static function setToUAccepted(int $user_id, int $instance_id = 0)
    {
        /** @var AcceptedToU $ar */
        if ($ar = AcceptedToU::where(["user_id" => $user_id, "oc_instance_id" => $instance_id])->first()) {
            $ar->setAccepted();
            $ar->update();
        } else {
            self::create($user_id, $instance_id, true);
        }
    }

    // ToDo: Remove default value for instance
    public static function resetForInstance(int $instance_id = 0) {
        if ($array = AcceptedToU::where(["oc_instance_id" => $instance_id])->get()) {
            /** @var AcceptedToU $ar */
            foreach ($array as $ar) {
                $ar->resetAccepted();
                $ar->update();
            }
        }
    }



}