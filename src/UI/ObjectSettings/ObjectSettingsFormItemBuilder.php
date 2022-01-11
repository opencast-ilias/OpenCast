<?php

namespace srag\Plugins\Opencast\UI\ObjectSettings;

use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Object\ObjectSettingsParser;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use xoctConf;
use xoctPermissionTemplate;
use xoctUserSettings;

class ObjectSettingsFormItemBuilder
{
    const F_COURSE_NAME = 'course_name';
    const F_TITLE = 'title';
    const F_DESCRIPTION = 'description';
    const F_INTRODUCTION_TEXT = 'introduction_text';
    const F_LICENSE = 'license';
    const F_DEPARTMENT = 'department';
    const F_STREAMING_ONLY = 'streaming_only';
    const F_USE_ANNOTATIONS = 'use_annotations';
    const F_PERMISSION_PER_CLIP = 'permission_per_clip';
    const F_PERMISSION_ALLOW_SET_OWN = 'permission_allow_set_own';
    const F_OBJ_ONLINE = 'obj_online';
    const F_VIDEO_PORTAL_LINK = 'video_portal_link';
    const F_MEMBER_UPLOAD = 'member_upload';
    const F_PUBLISH_ON_VIDEO_PORTAL = 'publish_on_video_portal';
    const F_PERMISSION_TEMPLATE = 'permission_template';
    const F_DEFAULT_VIEW = 'default_view';
    const F_VIEW_CHANGEABLE = 'view_changeable';
    const F_CHAT_ACTIVE = 'chat_active';

    /**
     * @var UIFactory
     */
    protected $ui_factory;
    /**
     * @var RefineryFactory
     */
    private $refinery_factory;
    /**
     * @var PublicationUsageRepository
     */
    private $publicationUsageRepository;
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var ObjectSettingsParser
     */
    private $objectSettingsParser;

    /**
     * @param UIFactory $ui_factory
     * @param RefineryFactory $refinery_factory
     * @param PublicationUsageRepository $publicationUsageRepository
     * @param ilPlugin $plugin
     */
    public function __construct(UIFactory                  $ui_factory,
                                RefineryFactory            $refinery_factory,
                                PublicationUsageRepository $publicationUsageRepository,
                                ObjectSettingsParser       $objectSettingsParser,
                                ilPlugin                   $plugin)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->publicationUsageRepository = $publicationUsageRepository;
        $this->plugin = $plugin;
        $this->objectSettingsParser = $objectSettingsParser;
    }

    public function create(): Input
    {
        $field_factory = $this->ui_factory->input()->field();
        $inputs = [
            self::F_OBJ_ONLINE => $field_factory->checkbox($this->txt(self::F_OBJ_ONLINE)),
            self::F_INTRODUCTION_TEXT => $field_factory->textarea($this->txt(self::F_INTRODUCTION_TEXT)),
            self::F_DEFAULT_VIEW => $field_factory->select($this->txt(self::F_DEFAULT_VIEW), [
                xoctUserSettings::VIEW_TYPE_LIST => $this->txt('view_type_' . xoctUserSettings::VIEW_TYPE_LIST),
                xoctUserSettings::VIEW_TYPE_TILES => $this->txt('view_type_' . xoctUserSettings::VIEW_TYPE_TILES),
            ])->withRequired(true),
            self::F_VIEW_CHANGEABLE => $field_factory->checkbox($this->txt(self::F_VIEW_CHANGEABLE),
                $this->txt(self::F_VIEW_CHANGEABLE . '_info'))
        ];
        if (xoctPermissionTemplate::count()) {
            $inputs[self::F_PUBLISH_ON_VIDEO_PORTAL] = $field_factory->optionalGroup([
                $this->getPermissionTemplateRadioInput()
            ], sprintf($this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL), xoctConf::getConfig(xoctConf::F_VIDEO_PORTAL_TITLE)),
                $this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL . '_info'));
        }

        if ($this->publicationUsageRepository->exists(PublicationUsage::USAGE_ANNOTATE)) {
            $inputs[self::F_USE_ANNOTATIONS] = $field_factory->checkbox($this->txt(self::F_USE_ANNOTATIONS));
        }

        if ($this->publicationUsageRepository->exists(PublicationUsage::USAGE_DOWNLOAD)) {
            $inputs[self::F_STREAMING_ONLY] = $field_factory->checkbox($this->txt(self::F_STREAMING_ONLY),
                $this->txt(self::F_STREAMING_ONLY . '_info'));
        }

        $inputs[self::F_PERMISSION_PER_CLIP] = $field_factory->optionalGroup([
            self::F_PERMISSION_ALLOW_SET_OWN => $field_factory->checkbox($this->txt(self::F_PERMISSION_ALLOW_SET_OWN),
                $this->txt(self::F_PERMISSION_ALLOW_SET_OWN . '_info'))
        ],
            $this->txt(self::F_PERMISSION_PER_CLIP),
            $this->txt(self::F_PERMISSION_PER_CLIP . '_info'));

        $inputs[self::F_MEMBER_UPLOAD] = $field_factory->checkbox($this->txt(self::F_MEMBER_UPLOAD),
            $this->txt(self::F_MEMBER_UPLOAD . '_info'));


        return $field_factory->section($inputs, $this->plugin->txt('object_settings'))
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
                $vs['object'] = $this->objectSettingsParser->parseFormData($vs);
                if (is_array($vs[self::F_PUBLISH_ON_VIDEO_PORTAL])) {
                    $vs['permission_template'] = $vs[self::F_PUBLISH_ON_VIDEO_PORTAL][0];
                    unset($vs[self::F_PUBLISH_ON_VIDEO_PORTAL]);
                }
                return $vs;
            }));
    }

    public function update(ObjectSettings $objectSettings)
    {
        $field_factory = $this->ui_factory->input()->field();
        $inputs = [
            self::F_OBJ_ONLINE => $field_factory->checkbox($this->txt(self::F_OBJ_ONLINE))->withValue($objectSettings->isOnline()),
            self::F_INTRODUCTION_TEXT => $field_factory->textarea($this->txt(self::F_INTRODUCTION_TEXT))->withValue($objectSettings->getIntroductionText()),
            self::F_DEFAULT_VIEW => $field_factory->select($this->txt(self::F_DEFAULT_VIEW), [
                xoctUserSettings::VIEW_TYPE_LIST => $this->txt('view_type_' . xoctUserSettings::VIEW_TYPE_LIST),
                xoctUserSettings::VIEW_TYPE_TILES => $this->txt('view_type_' . xoctUserSettings::VIEW_TYPE_TILES),
            ])->withRequired(true)->withValue($objectSettings->getDefaultView()),
            self::F_VIEW_CHANGEABLE => $field_factory->checkbox($this->txt(self::F_VIEW_CHANGEABLE),
                $this->txt(self::F_VIEW_CHANGEABLE . '_info'))->withValue($objectSettings->isViewChangeable())
        ];

        if (xoctPermissionTemplate::count()) {
            $inputs[self::F_PUBLISH_ON_VIDEO_PORTAL] = $field_factory->optionalGroup([
                $this->getPermissionTemplateRadioInput()
            ], sprintf($this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL), xoctConf::getConfig(xoctConf::F_VIDEO_PORTAL_TITLE)),
                $this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL . '_info'));
        }

        if ($this->publicationUsageRepository->exists(PublicationUsage::USAGE_ANNOTATE)) {
            $inputs[self::F_USE_ANNOTATIONS] = $field_factory->checkbox($this->txt(self::F_USE_ANNOTATIONS))->withValue($objectSettings->getUseAnnotations());
        }

        if ($this->publicationUsageRepository->exists(PublicationUsage::USAGE_DOWNLOAD)) {
            $inputs[self::F_STREAMING_ONLY] = $field_factory->checkbox($this->txt(self::F_STREAMING_ONLY),
                $this->txt(self::F_STREAMING_ONLY . '_info'))->withValue($objectSettings->getStreamingOnly());
        }

        $inputs[self::F_PERMISSION_PER_CLIP] = $field_factory->optionalGroup([
            self::F_PERMISSION_ALLOW_SET_OWN => $field_factory->checkbox($this->txt(self::F_PERMISSION_ALLOW_SET_OWN),
                $this->txt(self::F_PERMISSION_ALLOW_SET_OWN . '_info'))->withValue($objectSettings->getPermissionAllowSetOwn())
        ],
            $this->txt(self::F_PERMISSION_PER_CLIP),
            $this->txt(self::F_PERMISSION_PER_CLIP . '_info'))->withValue($objectSettings->getPermissionPerClip() ? self::F_PERMISSION_ALLOW_SET_OWN : null);


        return $field_factory->section($inputs, $this->plugin->txt('object_settings'))
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($vs) {
                $vs['object'] = $this->objectSettingsParser->parseFormData($vs);
                if (is_array($vs[self::F_PUBLISH_ON_VIDEO_PORTAL])) {
                    $vs['permission_template'] = $vs[self::F_PUBLISH_ON_VIDEO_PORTAL][0];
                    unset($vs[self::F_PUBLISH_ON_VIDEO_PORTAL]);
                }
                return $vs;
            }));
    }

    private function getPermissionTemplateRadioInput(): Input
    {
        $radio = $this->ui_factory->input()->field()->radio($this->txt(self::F_PERMISSION_TEMPLATE));
        /** @var xoctPermissionTemplate $ptpl */
        foreach (xoctPermissionTemplate::where(array('is_default' => 0))->orderBy('sort')->get() as $ptpl) {
            $radio = $radio->withOption($ptpl->getId(), $ptpl->getTitle(), $ptpl->getInfo() ?? null);
        }
        return $radio->withRequired(true);
    }

    private function txt(string $lang_var): string
    {
        return $this->plugin->txt('series_' . $lang_var);
    }
}