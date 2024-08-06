<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Metadata;

use DateTimeImmutable;
use DateTimeZone;
use ILIAS\DI\Container;
use ILIAS\Refinery\Custom\Transformation;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Input;
use ILIAS\UI\Factory as UIFactory;
use ilPlugin;
use ilTimeZone;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use xoctException;

/**
 * Responsible for building form sections for Metadata fields.
 * Should be split into multiple sub builders, since there are too many use-cases here (create, update, createScheduled, etc.)
 */
class MDFormItemBuilder
{
    public const LABEL_PREFIX = 'md_';
    public static array $binding_fields = [
        'title' => 'titleinput'
    ];

    public function __construct(protected MDCatalogue $md_catalogue, private readonly MDFieldConfigRepository $md_conf_repository, protected MDPrefiller $prefiller, protected UIFactory $ui_factory, private readonly RefineryFactory $refinery_factory, private readonly MDParser $MDParser, private readonly \ilPlugin $plugin, private readonly Container $dic)
    {
    }

    public function create_section(bool $as_admin): Input
    {
        return $this->ui_factory->input()->field()->section(
            $this->create_items($as_admin),
            $this->plugin->txt('metadata')
        )
                                ->withAdditionalTransformation($this->transformation());
    }

    /**
     * @return Input[]
     * @throws xoctException
     */
    public function create_items(bool $as_admin): array
    {
        $form_elements = [];
        $MDFieldConfigARS = $this->md_conf_repository->getAllEditable($as_admin);
        array_walk($MDFieldConfigARS, function (MDFieldConfigAR $md_field_config) use (&$form_elements): void {
            // TODO: visible for permission!
            $key = $this->prefixPostVar($md_field_config->getFieldId());
            $values = $this->prefiller->getReplacedPrefill($md_field_config->getPrefill());
            $form_elements[$key] = $this->buildFormElementForMDField(
                $md_field_config,
                $values
            );
        });
        return $form_elements;
    }

    public function update_section(Metadata $existing_metadata, bool $as_admin): Input
    {
        $form_elements = [];
        $stored_field_configurations = $this->md_conf_repository->getAll($as_admin);

        array_walk(
            $stored_field_configurations,
            function (MDFieldConfigAR $md_field_config) use (&$form_elements, $existing_metadata): void {
                $key = $this->prefixPostVar($md_field_config->getFieldId());

                $form_elements[$key] = $this->buildFormElementForMDField(
                    $md_field_config,
                    $existing_metadata->getField($md_field_config->getFieldId())->getValue()
                );
            }
        );
        return $this->ui_factory->input()->field()->section($form_elements, $this->plugin->txt('metadata'))
                                ->withAdditionalTransformation($this->transformation());
    }

    public function schedule_section(bool $as_admin): Input
    {
        $form_elements = [];
        $MDFieldConfigARS = array_filter(
            $this->md_conf_repository->getAllEditable($as_admin),
            fn(MDFieldConfigEventAR $fieldConfigAR): bool =>
                // start date is part of scheduling and location has a special input field
                !in_array(
                    $fieldConfigAR->getFieldId(),
                    [MDFieldDefinition::F_START_DATE, MDFieldDefinition::F_LOCATION]
                )
        );
        array_walk($MDFieldConfigARS, function (MDFieldConfigEventAR $md_field_config) use (&$form_elements): void {
            $key = $this->prefixPostVar($md_field_config->getFieldId());
            $form_elements[$key] = $this->buildFormElementForMDField(
                $md_field_config,
                $this->prefiller->getReplacedPrefill($md_field_config->getPrefill())
            );
        });
        return $this->ui_factory->input()->field()->section($form_elements, $this->plugin->txt('metadata'))
                                ->withAdditionalTransformation($this->transformation());
    }

    public function update_scheduled_section(Metadata $existing_metadata, bool $as_admin): Input
    {
        $form_elements = [];
        $MDFieldConfigARS = array_filter(
            $this->md_conf_repository->getAll($as_admin),
            fn(MDFieldConfigEventAR $fieldConfigAR): bool =>
                // start date is part of scheduling and location has a special input field
                !in_array(
                    $fieldConfigAR->getFieldId(),
                    [MDFieldDefinition::F_START_DATE, MDFieldDefinition::F_LOCATION]
                )
        );
        array_walk(
            $MDFieldConfigARS,
            function (MDFieldConfigEventAR $md_field_config) use (&$form_elements, $existing_metadata): void {
                $key = $this->prefixPostVar($md_field_config->getFieldId());
                $form_elements[$key] = $this->buildFormElementForMDField(
                    $md_field_config,
                    $existing_metadata->getField($md_field_config->getFieldId())->getValue()
                );
            }
        );
        return $this->ui_factory->input()->field()->section($form_elements, $this->plugin->txt('metadata'))
                                ->withAdditionalTransformation($this->transformation());
    }

    /**
     * @throws xoctException
     */
    public function buildFormElementForMDField(MDFieldConfigAR $fieldConfigAR, $value): Input
    {
        $md_definition = $this->md_catalogue->getFieldById($fieldConfigAR->getFieldId());
        $field = match ($md_definition->getType()->getTitle()) {
            MDDataType::TYPE_TEXT => $this->ui_factory->input()->field()->text(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey())
            ),
            MDDataType::TYPE_TEXT_ARRAY => $this->ui_factory->input()->field()->text(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey())
            )
                                      ->withAdditionalTransformation(
                                          $this->refinery_factory->custom()->transformation(
                                              fn(string $value): array => explode(',', $value)
                                          )
                                      ),
            MDDataType::TYPE_TEXT_SELECTION => $this->ui_factory->input()->field()->select(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey()),
                $fieldConfigAR->getValues()
            )->withValue(null),
            MDDataType::TYPE_TEXT_LONG => $this->ui_factory->input()->field()->textarea(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey())
            ),
            MDDataType::TYPE_TIME => $this->ui_factory->input()->field()->text(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey())
            )
                                      ->withByline($this->plugin->txt('byline_timeformat'))
                                      ->withAdditionalTransformation(
                                          $this->refinery_factory->custom()->constraint(fn($vs): bool => empty($vs) || preg_match(
                                              "/^(?:2[0-3]|[01]\\d):[0-5]\\d:[0-5]\\d\$/",
                                              (string) $vs
                                          ), $this->plugin->txt('msg_invalid_time_format'))
                                      ),
            MDDataType::TYPE_DATETIME => $this->ui_factory->input()->field()->dateTime(
                $fieldConfigAR->getTitle($this->dic->language()->getLangKey())
            )->withUseTime(true),
            default => throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Unknown MDDataType: ' . $md_definition->getType()->getTitle()
            ),
        };
        if (in_array($fieldConfigAR->getFieldId(), array_keys(self::$binding_fields))) {
            $binding_data = self::$binding_fields[$fieldConfigAR->getFieldId()];
            $field = $field->withAdditionalOnLoadCode(
                fn(string $id): string => '
                        $("#' . $id . '").attr("data-' . $binding_data . '", "bind");
                    '
            );
        }
        $field = $field
            ->withRequired(
                $fieldConfigAR->isRequired(),
                // Custom required constraint, to provide better error message.
                $this->refinery_factory->custom()->constraint(
                    fn($value): bool => !empty(trim((string) $value)),
                    $this->plugin->txt('msg_empty_required_field')
                )
            )
            ->withDisabled($fieldConfigAR->isReadOnly());
        return $value ? $field->withValue($this->formatValue($value, $md_definition, $fieldConfigAR)) : $field;
    }

    private function formatValue($value, MDFieldDefinition $md_definition, MDFieldConfigAR $fieldConfigAR)
    {
        switch ($md_definition->getType()->getTitle()) {
            case MDDataType::TYPE_DATETIME:
                /** @var $value DateTimeImmutable */
                return $value instanceof DateTimeImmutable ? $value->setTimezone(
                    new DateTimeZone(ilTimeZone::_getDefaultTimeZone())
                )->format('Y-m-d H:i:s') : $value;
            case MDDataType::TYPE_TEXT_ARRAY:
                return is_array($value) ? implode(',', $value) : $value;
            case MDDataType::TYPE_TEXT_SELECTION:
                if (!array_key_exists($value, $fieldConfigAR->getValues())) {
                    return null;
                }
                return $value;
            default:
                return $value;
        }
    }

    public function prefixPostVar(string $label): string
    {
        return self::LABEL_PREFIX . $label;
    }

    public function transformation(): Transformation
    {
        return $this->refinery_factory->custom()->transformation(function (array $vs): array {
            // todo: remove this ugly instance check (maybe create subclasses MDEventFormItemBuilder and MDSeriesFormItemBuilder)
            $vs['object'] = ($this->md_conf_repository instanceof MDFieldConfigEventRepository) ?
                $this->MDParser->parseFormDataEvent($vs)
                : $this->MDParser->parseFormDataSeries($vs);
            return $vs;
        });
    }

    public function parser(): MDParser
    {
        return $this->MDParser;
    }
}
