<?php

namespace srag\Plugins\Opencast\Model\Object;

use srag\Plugins\Opencast\UI\ObjectSettings\ObjectSettingsFormItemBuilder;

class ObjectSettingsParser
{
    public function parseFormData(array $data): ObjectSettings
    {
        $objectSettings = new ObjectSettings();
        $objectSettings->setOnline($data[ObjectSettingsFormItemBuilder::F_OBJ_ONLINE]);
        $objectSettings->setIntroductionText($data[ObjectSettingsFormItemBuilder::F_INTRODUCTION_TEXT]);
        $objectSettings->setDefaultView($data[ObjectSettingsFormItemBuilder::F_DEFAULT_VIEW]);
        $objectSettings->setViewChangeable($data[ObjectSettingsFormItemBuilder::F_VIEW_CHANGEABLE]);
        $objectSettings->setUseAnnotations($data[ObjectSettingsFormItemBuilder::F_USE_ANNOTATIONS] ?? false);
        $objectSettings->setStreamingOnly($data[ObjectSettingsFormItemBuilder::F_STREAMING_ONLY] ?? false);
        $objectSettings->setPermissionPerClip(is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP]));
        $objectSettings->setPermissionAllowSetOwn(
            is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP])
            && $data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP][ObjectSettingsFormItemBuilder::F_PERMISSION_ALLOW_SET_OWN]
        );
        $objectSettings->setChatActive($data[ObjectSettingsFormItemBuilder::F_CHAT_ACTIVE] ?? false);

        return $objectSettings;
    }
}
