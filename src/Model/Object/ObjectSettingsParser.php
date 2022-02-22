<?php

namespace srag\Plugins\Opencast\Model\Object;

use srag\Plugins\Opencast\UI\ObjectSettings\ObjectSettingsFormItemBuilder;

class ObjectSettingsParser
{
    public function parseFormData(array $data) : ObjectSettings
    {
        $objectSettings = new ObjectSettings();
        $objectSettings->setOnline($data[ObjectSettingsFormItemBuilder::F_OBJ_ONLINE]);
        $objectSettings->setIntroductionText($data[ObjectSettingsFormItemBuilder::F_INTRODUCTION_TEXT]);
        $objectSettings->setDefaultView($data[ObjectSettingsFormItemBuilder::F_DEFAULT_VIEW]);
        $objectSettings->setViewChangeable($data[ObjectSettingsFormItemBuilder::F_VIEW_CHANGEABLE]);
        $objectSettings->setUseAnnotations($data[ObjectSettingsFormItemBuilder::F_USE_ANNOTATIONS] ?? false);
        $objectSettings->setStreamingOnly($data[ObjectSettingsFormItemBuilder::F_STREAMING_ONLY] ?? false);
        $objectSettings->setPermissionPerClip(is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP]));
        $objectSettings->setPermissionAllowSetOwn(is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP])
            && $data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP][ObjectSettingsFormItemBuilder::F_PERMISSION_ALLOW_SET_OWN]);
        $objectSettings->setChatActive($data[ObjectSettingsFormItemBuilder::F_CHAT_ACTIVE] ?? false);
        if (isset($data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_OPTION])) {
            $paella_player_option = $data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_OPTION][0];
            $objectSettings->setPaellaPlayerOption($paella_player_option);
            if ($paella_player_option === ObjectSettings::PAELLA_OPTION_URL) {
                $objectSettings->setPaellaPlayerUrl($data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_OPTION][1]['url']);
            } else if ($paella_player_option === ObjectSettings::PAELLA_OPTION_FILE) {
                if ($file_id = $data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_OPTION][1]['file'][0]) {
                    $objectSettings->setPaellaPlayerFileId($file_id);
                }
            }
        }
        if (isset($data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_LIVE_OPTION])) {
            $paella_player_option = $data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_LIVE_OPTION][0];
            $objectSettings->setPaellaPlayerLiveOption($paella_player_option);
            if ($paella_player_option === ObjectSettings::PAELLA_OPTION_URL) {
                $objectSettings->setPaellaPlayerLiveUrl($data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_LIVE_OPTION][1]['url']);
            } else if ($paella_player_option === ObjectSettings::PAELLA_OPTION_FILE) {
                if ($file_id = $data[ObjectSettingsFormItemBuilder::F_PAELLA_PLAYER_LIVE_OPTION][1]['file'][0]) {
                    $objectSettings->setPaellaPlayerLiveFileId($file_id);
                }
            }
        }
        return $objectSettings;
    }
}