<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\UI\Metadata\MDFormItemBuilder;
use xoctSeries;
use xoctUser;

class FormBuilderSeries
{
    const EXISTING_NO = 1;
    const EXISTING_YES = 2;

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

    public function __construct(UIFactory         $ui_factory,
                                Refinery          $refinery,
                                MDParser          $mdParser,
                                MDFormItemBuilder $formItemBuilder,
                                ilPlugin          $plugin,
                                Container         $dic)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->mdParser = $mdParser;
        $this->formItemBuilder = $formItemBuilder;
        $this->plugin = $plugin;
        $this->dic = $dic;
    }


    public function create(string $form_action): Standard
    {
        $existing_series = array();
        $xoctUser = xoctUser::getInstance($this->dic->user());
        $user_series = xoctSeries::getAllForUser($xoctUser->getUserRoleName());
        foreach ($user_series as $serie) {
            $existing_series[$serie->getIdentifier()] = $serie->getTitle() . ' (...' . substr($serie->getIdentifier(), - 4, 4) . ')';
        }
        array_multisort($existing_series);
        $series_type = $this->ui_factory->input()->field()->switchableGroup([
            self::EXISTING_YES => $this->ui_factory->input()->field()->group([
                $this->ui_factory->input()->field()->select('Series', $existing_series)
            ]), 'Existing Series'
        ]);
        $series_type = $this->ui_factory->input()->field()->radio($this->plugin->txt('series_channel_type'))
            ->withOption(self::EXISTING_YES, $this->plugin->txt('series_existing_channel_yes'))
            ->withOption(self::EXISTING_NO, $this->plugin->txt('series_existing_channel_no'));
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $this->formItemBuilder->series_create()
        );
    }
}