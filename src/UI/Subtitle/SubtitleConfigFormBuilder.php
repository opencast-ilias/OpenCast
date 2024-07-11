<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\SubtitleConfig;

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ilPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\ListProvider\ListProvider;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class SubtitleConfigFormBuilder
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class SubtitleConfigFormBuilder
{
    use LocaleTrait;

    public const F_SUBTITLE_UPLOAD_ENABLED = 'subtitle_upload_enabled';
    public const F_SUBTITLE_ACCEPTED_MIMETYPES = 'subtitle_accepted_mimetypes';
    public const F_SUBTITLE_LANGS = 'subtitle_languages';
    public const LANG_VALUE_SEPARATOR = '|||';
    private static $accepted_subtitle_extensions = [
        'text/vtt' => '*.vtt',
        '.srt' => '*.srt',
        '.ass' => '*.ass',
        '.ssa' => '*.ssa',
    ];
    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var Factory
     */
    private $ui_factory;
    /**
     * @var Renderer
     */
    private $ui_renderer;

    public function __construct(
        ilPlugin $plugin,
        Factory $ui_factory,
        Renderer $ui_renderer
    ) {
        $this->plugin = $plugin;
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
    }

    /**
     * Builds the form.
     *
     * @param string $form_action the form action url
     * @param bool $load_languages optional flag to determine whether to load the languages from listproviders. default false
     *
     * @return Standard UI Componenet Standard form
     */
    public function buildForm(string $form_action, bool $load_languages = false): Standard
    {
        $dependant_fields = [];
        // Accepted mimetypes.
        $selected_types = (array) PluginConfig::getConfig(PluginConfig::F_SUBTITLE_ACCEPTED_MIMETYPES) ?? [];
        $dependant_fields[self::F_SUBTITLE_ACCEPTED_MIMETYPES] = $this->ui_factory->input()->field()
            ->multiselect(
                $this->txt(
                    self::F_SUBTITLE_ACCEPTED_MIMETYPES
                ),
                self::$accepted_subtitle_extensions,
                $this->txt(
                    self::F_SUBTITLE_ACCEPTED_MIMETYPES . '_info'
                )
            )
            ->withRequired(true)
            ->withValue($selected_types);

        // Supported languages.
        $info = vsprintf(
            $this->txt(self::F_SUBTITLE_LANGS . '_info'),
            array_fill(0, 3, self::LANG_VALUE_SEPARATOR)
        );
        $languages_value = $this->getFormatedLanguages($load_languages);
        $dependant_fields[self::F_SUBTITLE_LANGS] = $this->ui_factory->input()->field()
            ->textarea(
                $this->txt(self::F_SUBTITLE_LANGS),
                $info
            )
            ->withRequired(true)
            ->withValue($languages_value);

        // Main enable upload subtitle option.
        $inputs = [];
        $optional_group = $this->ui_factory->input()->field()->optionalGroup(
            $dependant_fields,
            $this->txt(
                self::F_SUBTITLE_UPLOAD_ENABLED
            ),
            $this->txt(
                self::F_SUBTITLE_UPLOAD_ENABLED . '_info'
            ),
        );

        if (PluginConfig::getConfig(PluginConfig::F_SUBTITLE_UPLOAD_ENABLED) == false && !$load_languages) {
            $optional_group = $optional_group->withValue(null);
        }
        $inputs[self::F_SUBTITLE_UPLOAD_ENABLED] = $optional_group;

        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    /**
     * Helper function to either load the languages from listprovider or saved configs and make sure it is formatted.
     *
     * @param bool $load_languages optional flag to determine whether to load the languages from listproviders. default false
     *
     * @return string formatted languages list
     */
    private function getFormatedLanguages(bool $load_languages = false): string
    {
        $formatted_languages_str = PluginConfig::getConfig(PluginConfig::F_SUBTITLE_LANGS) ?? '';
        if (!$load_languages) {
            return $formatted_languages_str;
        }

        // As a form of fallback to what was saved before, we convert the formatted lang str to array here.
        $formatted_languages_arr = $this->formattedLanguagesToArray($formatted_languages_str);
        $listprovider = new ListProvider();
        $source = 'LANGUAGES';
        if ($listprovider->hasList($source)) {
            $digested_list = [];
            $language_list = $listprovider->getList($source);
            foreach ($language_list as $key => $value) {
                $split = explode('.', $value);
                $default_text = ucfirst(strtolower($split[count($split) - 1]));
                $translated = $this->getLocaleString(
                    'md_lang_list_' . $key,
                    '',
                    $default_text
                );
                $digested_list[$key] = $translated;
            }
            if (!empty($digested_list)) {
                $formatted_languages_arr = $digested_list;
            }
        }
        return $this->languagesArrayToFormatttedString($formatted_languages_arr);
    }

    /**
     * Gets the lang strings.
     *
     * @param string $string The lang string key
     *
     * @return string the lang string value
     */
    private function txt(string $string): string
    {
        return $this->getLocaleString($string, 'config');
    }

    /**
     * Helper function to covert the string formatted lanugaes to array of key value pairs.
     *
     * @param string $formatted_languages the string of languages
     *
     * @return array the array of languages in key value pairs
     */
    public function formattedLanguagesToArray(string $formatted_languages): array
    {
        $languages_array = [];
        if (empty($formatted_languages)) {
            return [];
        }
        // normalize line endings
        $values = str_replace("\r\n", "\n", $formatted_languages);
        foreach (explode("\n", $values) as $value) {
            $value = explode(self::LANG_VALUE_SEPARATOR, $value);
            if (count($value) === 2) {
                $languages_array[$value[0]] = $value[1];
            }
        }
        return $languages_array;
    }

    /**
     * Helper function to convert the array of languages to formatted string.
     *
     * @param array $languages the key-value pairs of languages to convert.
     *
     * @return string the formatted languages string
     */
    private function languagesArrayToFormatttedString(array $languages): string
    {
        if (empty($languages)) {
            return '';
        }
        $separator = self::LANG_VALUE_SEPARATOR;
        $converted_list = array_map(function ($key, $value) use ($separator) {
            return "{$key}{$separator}{$value}";
        }, array_keys($languages), array_values($languages));
        return !empty($converted_list) ? implode("\n", $converted_list) : '';
    }
}
