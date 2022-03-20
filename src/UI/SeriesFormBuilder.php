<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Series\Series;
use srag\Plugins\Opencast\Model\Series\SeriesRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use srag\Plugins\Opencast\UI\ObjectSettings\ObjectSettingsFormItemBuilder;
use xoctException;

/**
 * Responsible for creating forms to create or edit a series/an ILIAS Opencast object.
 * Delegates stuff to other builders, like ObjectSettingsFormItemBuilder for ObjectSettings.
 */
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
    /**
     * @var SeriesRepository
     */
    private $seriesRepository;

    public function __construct(
        UIFactory $ui_factory,
        Refinery $refinery,
        MDFormItemBuilder $formItemBuilder,
        ObjectSettingsFormItemBuilder $objectSettingsFormItemBuilder,
        SeriesRepository $seriesRepository,
        ilPlugin $plugin,
        Container $dic
    ) {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->formItemBuilder = $formItemBuilder;
        $this->plugin = $plugin;
        $this->dic = $dic;
        $this->objectSettingsFormItemBuilder = $objectSettingsFormItemBuilder;
        $this->seriesRepository = $seriesRepository;
    }

    public function create(string $form_action) : Standard
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'series_type' => $this->buildSeriesSelectionSection(true),
                'settings' => $this->objectSettingsFormItemBuilder->create()
            ]

        );
    }

    public function update(
        string $form_action,
        ObjectSettings $objectSettings,
        Series $series,
        bool $is_admin
    ) : Standard {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'metadata' => $this->formItemBuilder->update_section($series->getMetadata(), $is_admin),
                'settings' => $this->objectSettingsFormItemBuilder->update($objectSettings, $series),
            ]
        );
    }

    /**
     * @return array
     * @throws xoctException
     */
    private function getSeriesSelectOptions() : array
    {
        $existing_series = array();
        $xoctUser = xoctUser::getInstance($this->dic->user());
        if (is_null($xoctUser->getUserRoleName()) !== true) {
            $user_series = $this->seriesRepository->getAllForUser($xoctUser->getUserRoleName());
            foreach ($user_series as $series) {
                $existing_series[$series->getIdentifier()] = $series->getMetadata()->getField(MDFieldDefinition::F_TITLE)->getValue() . ' (...' . substr($series->getIdentifier(),
                        -4, 4) . ')';
            }
            array_multisort($existing_series);
            return $existing_series;
        }
        return [];
    }

    private function txt(string $lang_var) : string
    {
        return $this->plugin->txt('series_' . $lang_var);
    }

    /**
     * @param bool $is_admin
     * @return Input
     * @throws xoctException
     */
    private function buildSeriesSelectionSection(bool $is_admin) : Input
    {
        $existing_series = $this->getSeriesSelectOptions();
        $series_type = $this->ui_factory->input()->field()->switchableGroup([
            self::EXISTING_YES => $this->ui_factory->input()->field()->group([
                self::F_CHANNEL_ID => $this->ui_factory->input()->field()->select($this->txt(self::F_CHANNEL_ID),
                    $existing_series)->withRequired(true)
            ], $this->plugin->txt('yes')),
            self::EXISTING_NO => $this->ui_factory->input()->field()->group($this->formItemBuilder->create_items($is_admin),
                $this->plugin->txt('no'))
        ], 'Existing Series')->withValue(self::EXISTING_NO);
        return $this->ui_factory->input()->field()->section([self::F_EXISTING_IDENTIFIER => $series_type],
            $this->plugin->txt(self::F_CHANNEL_TYPE))
                                ->withAdditionalTransformation($this->refinery->custom()->transformation(function ($vs
                                ) {
                                    if ($vs[self::F_EXISTING_IDENTIFIER][0] == self::EXISTING_YES) {
                                        $vs[self::F_CHANNEL_ID] = $vs[self::F_EXISTING_IDENTIFIER][1][self::F_CHANNEL_ID];
                                    } else {
                                        $vs[self::F_CHANNEL_ID] = false;
                                        $vs['metadata'] = $this->formItemBuilder->parser()->parseFormDataSeries($vs[self::F_EXISTING_IDENTIFIER][1]);
                                    }
                                    unset($vs[self::F_EXISTING_IDENTIFIER]);
                                    return $vs;
                                }));
    }
}