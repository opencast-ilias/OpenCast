<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ILIAS\UI\Component\Input\Input;
use ILIAS\DI\Container;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UIFactory;
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
    public const F_CHANNEL_TYPE = 'channel_type';
    public const EXISTING_NO = 1;
    public const EXISTING_YES = 2;
    public const F_CHANNEL_ID = 'channel_id';
    public const F_EXISTING_IDENTIFIER = 'existing_identifier';

    public function __construct(private readonly UIFactory $ui_factory, private readonly Refinery $refinery, private readonly MDFormItemBuilder $formItemBuilder, private readonly ObjectSettingsFormItemBuilder $objectSettingsFormItemBuilder, private readonly SeriesRepository $seriesRepository, private readonly \ilPlugin $plugin, private readonly Container $dic)
    {
    }

    public function create(string $form_action): Standard
    {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'series_type' => $this->buildSeriesSelectionSection(true),
                'settings' => $this->objectSettingsFormItemBuilder->create(),
                'member_rights' => $this->objectSettingsFormItemBuilder->memberRightsSection()
            ]
        );
    }

    public function update(
        string $form_action,
        ObjectSettings $objectSettings,
        Series $series,
        bool $is_admin
    ): Standard {
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            [
                'metadata' => $this->formItemBuilder->update_section($series->getMetadata(), $is_admin),
                'settings' => $this->objectSettingsFormItemBuilder->update($objectSettings, $series),
            ]
        );
    }

    /**
     * @throws xoctException
     */
    private function getSeriesSelectOptions(): array
    {
        $existing_series = [];
        $xoctUser = xoctUser::getInstance($this->dic->user());

        if ($xoctUser->getUserRoleName() === null) {
            return $existing_series;
        }

        $user_series = $this->seriesRepository->getAllForUser($xoctUser->getUserRoleName());
        foreach ($user_series as $series) {
            $existing_series[$series->getIdentifier()] = $series->getMetadata()->getField(
                MDFieldDefinition::F_TITLE
            )->getValue() . ' (...' . substr(
                $series->getIdentifier(),
                -4,
                4
            ) . ')';
        }
        array_multisort($existing_series);
        return $existing_series;
    }

    private function txt(string $lang_var): string
    {
        return $this->plugin->txt('series_' . $lang_var);
    }

    /**
     * @throws xoctException
     */
    private function buildSeriesSelectionSection(bool $is_admin): Input
    {
        $existing_series = $this->getSeriesSelectOptions();
        $series_type = $this->ui_factory->input()->field()->switchableGroup([
            self::EXISTING_YES => $this->ui_factory->input()->field()->group([
                self::F_CHANNEL_ID => $this->ui_factory->input()->field()->select(
                    $this->txt(self::F_CHANNEL_ID),
                    $existing_series
                )->withRequired(true)
            ], $this->plugin->txt('yes')),
            self::EXISTING_NO => $this->ui_factory->input()->field()->group(
                $this->formItemBuilder->create_items($is_admin),
                $this->plugin->txt('no')
            )
        ], $this->plugin->txt('series_existing_series'))->withValue(self::EXISTING_NO);
        return $this->ui_factory->input()->field()->section(
            [self::F_EXISTING_IDENTIFIER => $series_type],
            $this->plugin->txt(self::F_CHANNEL_TYPE)
        )
                                ->withAdditionalTransformation(
                                    $this->refinery->custom()->transformation(function (
                                        array $vs
                                    ): array {
                                        if ($vs[self::F_EXISTING_IDENTIFIER][0] == self::EXISTING_YES) {
                                            $vs[self::F_CHANNEL_ID] = $vs[self::F_EXISTING_IDENTIFIER][1][self::F_CHANNEL_ID];
                                        } else {
                                            $vs[self::F_CHANNEL_ID] = false;
                                            $vs['metadata'] = $this->formItemBuilder->parser()->parseFormDataSeries(
                                                $vs[self::F_EXISTING_IDENTIFIER][1]
                                            );
                                        }
                                        unset($vs[self::F_EXISTING_IDENTIFIER]);
                                        return $vs;
                                    })
                                );
    }
}
