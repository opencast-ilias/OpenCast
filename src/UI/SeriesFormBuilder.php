<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\UI\ObjectSettings\ObjectSettingsFormItemBuilder;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use xoctConf;
use xoctException;
use xoctSeries;
use xoctUser;

class SeriesFormBuilder
{
    const F_CHANNEL_TYPE = 'channel_type';
    const EXISTING_NO = 1;
    const EXISTING_YES = 2;
    const F_CHANNEL_ID = 'channel_id';
    const F_EXISTING_IDENTIFIER = 'existing_identifier';

    /**
     * @var UIFactory
     */
    private $ui_factory;
    /**
     * @var Refinery
     */
    private $refinery;
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
     * @var ObjectSettingsFormItemBuilder
     */
    private $objectSettingsFormItemBuilder;

    public function __construct(UIFactory                     $ui_factory,
                                Refinery                      $refinery,
                                MDFormItemBuilder             $formItemBuilder,
                                ObjectSettingsFormItemBuilder $objectSettingsFormItemBuilder,
                                ilPlugin                      $plugin,
                                Container                     $dic)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->formItemBuilder = $formItemBuilder;
        $this->plugin = $plugin;
        $this->dic = $dic;
        $this->objectSettingsFormItemBuilder = $objectSettingsFormItemBuilder;
    }


    public function create(string $form_action): Standard
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'series_type' => $this->buildSeriesSelectionSection(),
                'metadata' => $this->formItemBuilder->create(),
                'settings' => $this->objectSettingsFormItemBuilder->create()
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

    private function buildSeriesSelectionSection(): Input
    {
        $existing_series = $this->getSeriesSelectOptions();
        $series_type = $this->ui_factory->input()->field()->switchableGroup([
            self::EXISTING_YES => $this->ui_factory->input()->field()->group([
                self::F_CHANNEL_ID => $this->ui_factory->input()->field()->select($this->txt(self::F_CHANNEL_ID), $existing_series)->withRequired(true)
            ], $this->plugin->txt('yes')),
            self::EXISTING_NO => $this->ui_factory->input()->field()->group([], $this->plugin->txt('no'))
        ], 'Existing Series')->withValue(self::EXISTING_NO);
        return $this->ui_factory->input()->field()->section([self::F_EXISTING_IDENTIFIER => $series_type], $this->plugin->txt(self::F_CHANNEL_TYPE))
            ->withAdditionalTransformation($this->refinery->custom()->transformation(function ($vs) {
                if ($vs[self::F_EXISTING_IDENTIFIER][0] == self::EXISTING_YES) {
                    $vs[self::F_EXISTING_IDENTIFIER] = $vs[self::F_EXISTING_IDENTIFIER][1][self::F_CHANNEL_ID];
                } else {
                    $vs[self::F_EXISTING_IDENTIFIER] = false;
                }
                return $vs;
            }));
    }
}