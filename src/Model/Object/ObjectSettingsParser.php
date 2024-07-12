<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Object;

use srag\Plugins\Opencast\UI\ObjectSettings\ObjectSettingsFormItemBuilder;

class ObjectSettingsParser
{
    public function parseFormData(array $data): ObjectSettings
    {
        $objectSettings = new ObjectSettings();
        $objectSettings->setOnline((bool) ($data[ObjectSettingsFormItemBuilder::F_OBJ_ONLINE] ?? false));
        $objectSettings->setIntroductionText(
            (string) ($data[ObjectSettingsFormItemBuilder::F_INTRODUCTION_TEXT] ?? '')
        );
        $objectSettings->setDefaultView((int) ($data[ObjectSettingsFormItemBuilder::F_DEFAULT_VIEW] ?? 0));
        $objectSettings->setViewChangeable((bool) ($data[ObjectSettingsFormItemBuilder::F_VIEW_CHANGEABLE] ?? false));
        $objectSettings->setUseAnnotations((bool) ($data[ObjectSettingsFormItemBuilder::F_USE_ANNOTATIONS] ?? false));
        $objectSettings->setPermissionPerClip(
            is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP] ?? null)
        );
        $objectSettings->setPermissionAllowSetOwn(
            is_array($data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP] ?? null)
            && $data[ObjectSettingsFormItemBuilder::F_PERMISSION_PER_CLIP][ObjectSettingsFormItemBuilder::F_PERMISSION_ALLOW_SET_OWN] ?? false
        );
        $objectSettings->setChatActive((bool) ($data[ObjectSettingsFormItemBuilder::F_CHAT_ACTIVE] ?? false));

        return $objectSettings;
    }
}
