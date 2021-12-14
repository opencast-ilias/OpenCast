<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Publication\PublicationRepository;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use xoctConf;
use xoctException;
use xoctPermissionTemplate;
use xoctSeries;
use xoctUser;
use xoctUserSettings;

class SeriesFormBuilder
{
    const F_COURSE_NAME = 'course_name';
    const F_TITLE = 'title';
    const F_DESCRIPTION = 'description';
    const F_CHANNEL_TYPE = 'channel_type';
    const EXISTING_NO = 1;
    const EXISTING_YES = 2;
    const F_INTRODUCTION_TEXT = 'introduction_text';
    const F_INTENDED_LIFETIME = 'intended_lifetime';
    const F_EST_VIDEO_LENGTH = 'est_video_length';
    const F_LICENSE = 'license';
    const F_DISCIPLINE = 'discipline';
    const F_DEPARTMENT = 'department';
    const F_STREAMING_ONLY = 'streaming_only';
    const F_USE_ANNOTATIONS = 'use_annotations';
    const F_PERMISSION_PER_CLIP = 'permission_per_clip';
    const F_ACCEPT_EULA = 'accept_eula';
    const F_EXISTING_IDENTIFIER = 'existing_identifier';
    const F_PERMISSION_ALLOW_SET_OWN = 'permission_allow_set_own';
    const F_OBJ_ONLINE = 'obj_online';
    const F_VIDEO_PORTAL_LINK = 'video_portal_link';
    const F_CHANNEL_ID = 'channel_id';
    const F_MEMBER_UPLOAD = 'member_upload';
    const F_SHOW_UPLOAD_TOKEN = 'show_upload_token';
    const F_PUBLISH_ON_VIDEO_PORTAL = 'publish_on_video_portal';
    const F_PERMISSION_TEMPLATE = 'permission_template';
    const F_DEFAULT_VIEW = 'default_view';
    const F_VIEW_CHANGEABLE = 'view_changeable';
    const F_CHAT_ACTIVE = 'chat_active';

    /**
     * @var UIFactory
     */
    private $ui_factory;
    /**
     * @var Refinery
     */
    private $refinery;
    /**
     * @var MDParser
     */
    private $mdParser;
    /**
     * @var MDFormItemBuilder
     */
    private $formItemBuilder;
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var PublicationRepository
     */
    private $publicationRepository;

    public function __construct(UIFactory             $ui_factory,
                                Refinery              $refinery,
                                MDParser              $mdParser,
                                MDFormItemBuilder     $formItemBuilder,
                                PublicationRepository $publicationRepository,
                                ilPlugin              $plugin,
                                Container             $dic)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->mdParser = $mdParser;
        $this->formItemBuilder = $formItemBuilder;
        $this->plugin = $plugin;
        $this->dic = $dic;
        $this->publicationRepository = $publicationRepository;
    }


    public function create(string $form_action): Standard
    {
        $existing_series = $this->getSeriesSelectOptions();
        $series_type = $this->ui_factory->input()->field()->switchableGroup([
            self::EXISTING_YES => $this->ui_factory->input()->field()->group([
                self::F_CHANNEL_ID => $this->ui_factory->input()->field()->select($this->txt(self::F_CHANNEL_ID), $existing_series)->withRequired(true)
            ], $this->plugin->txt('yes')),
            self::EXISTING_NO => $this->ui_factory->input()->field()->group([], $this->plugin->txt('no'))
        ], 'Existing Series')->withValue(self::EXISTING_NO);
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'series_type' => $this->ui_factory->input()->field()->section([$series_type], $this->plugin->txt(self::F_CHANNEL_TYPE)),
                'metadata' => $this->formItemBuilder->create(),
                'settings' => $this->buildSettingsSection()
            ]

        );
    }

    /**
     * @return array
     * @throws xoctException
     */
    private function getSeriesSelectOptions(): array
    {
        $existing_series = array();
        $xoctUser = xoctUser::getInstance($this->dic->user());
        $user_series = xoctSeries::getAllForUser($xoctUser->getUserRoleName());
        foreach ($user_series as $serie) {
            $existing_series[$serie->getIdentifier()] = $serie->getTitle() . ' (...' . substr($serie->getIdentifier(), -4, 4) . ')';
        }
        array_multisort($existing_series);
        return $existing_series;
    }

    private function buildSettingsSection(): Input
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
        // todo: introduce repository
        if (xoctPermissionTemplate::count()) {
            $inputs[] = $field_factory->optionalGroup([
                $this->getPermissionTemplateRadioInput()
            ], sprintf($this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL), xoctConf::getConfig(xoctConf::F_VIDEO_PORTAL_TITLE)),
                $this->txt(self::F_PUBLISH_ON_VIDEO_PORTAL . '_info'));
        }
        return $field_factory->section($inputs, $this->plugin->txt('object_settings'));
    }

    private function getPermissionTemplateRadioInput(): Input
    {
        $radio = $this->ui_factory->input()->field()->radio($this->txt(self::F_PERMISSION_TEMPLATE));
        /** @var xoctPermissionTemplate $ptpl */
        foreach (xoctPermissionTemplate::where(array('is_default' => 0))->orderBy('sort')->get() as $ptpl) {
            $radio = $radio->withOption($ptpl->getId(), $ptpl->getTitle(), $ptpl->getInfo() ?? null);
        }
        return $radio;
    }

    private function getLicenseSelectOptions(): array
    {
        // TODO: is license metadata?
        $options = array(
            null => 'As defined in content',
        );
        $licenses = xoctConf::getConfig(xoctConf::F_LICENSES);
        if ($licenses) {
            foreach (explode("\n", $licenses) as $nl) {
                $lic = explode("#", $nl);
                if ($lic[0] && $lic[1]) {
                    $options[$lic[0]] = $lic[1];
                }
            }
        }
        return $options;
    }

    private function txt(string $lang_var): string
    {
        return $this->plugin->txt('series_' . $lang_var);
    }
}