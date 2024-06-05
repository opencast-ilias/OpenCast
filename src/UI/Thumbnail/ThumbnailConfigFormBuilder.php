<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\ThumbnailConfig;

use ILIAS\UI\Component\Input\Container\Form\Standard;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\Util\MimeType as MimeTypeUtil;

/**
 * Class ThumbnailConfigFormBuilder
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ThumbnailConfigFormBuilder
{
    use LocaleTrait;

    public const F_THUMBNAIL_UPLOAD_ENABLED = 'thumbnail_upload_enabled';
    public const F_THUMBNAIL_UPLOAD_MODE = 'thumbnail_upload_mode';
    public const F_THUMBNAIL_UPLOAD_MODE_FILE = 'thumbnail_upload_mode_file';
    public const F_THUMBNAIL_UPLOAD_MODE_TIMEPOINT = 'thumbnail_upload_mode_timepoint';
    public const F_THUMBNAIL_UPLOAD_MODE_BOTH = 'thumbnail_upload_mode_both';
    public const F_THUMBNAIL_ACCEPTED_MIMETYPES = 'thumbnail_accepted_mimetypes';
    private static array $accepted_thumbnail_extensions = [
        MimeTypeUtil::IMAGE__JPEG => '.jpg',
        MimeTypeUtil::IMAGE__PNG => '.png',
    ];
    // private $plugin;
    private Factory $ui_factory;
    private Renderer $ui_renderer;

    public function __construct(
        Factory $ui_factory,
        Renderer $ui_renderer
    ) {
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
    }

    /**
     * Builds the form.
     *
     * @param string $form_action the form action url     *
     * @return Standard UI Componenet Standard form
     */
    public function buildForm(string $form_action): Standard
    {
        $dependant_fields = [];
        // Accepted mimetypes.
        $selected_types = (array) (PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_ACCEPTED_MIMETYPES)
            ?? [MimeTypeUtil::IMAGE__JPEG]);

        $dependant_fields[self::F_THUMBNAIL_ACCEPTED_MIMETYPES] = $this->ui_factory->input()->field()
            ->multiselect(
                $this->txt(self::F_THUMBNAIL_ACCEPTED_MIMETYPES),
                self::$accepted_thumbnail_extensions,
                $this->txt(self::F_THUMBNAIL_ACCEPTED_MIMETYPES . '_info')
            )
            ->withRequired(true)
            ->withValue($selected_types);

        // Modes.
        $selected_mode = PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_UPLOAD_MODE) ?? self::F_THUMBNAIL_UPLOAD_MODE_BOTH;
        $dependant_fields[self::F_THUMBNAIL_UPLOAD_MODE] = $this->ui_factory->input()->field()
            ->radio(
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE),
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE . '_info')
            )
            ->withOption(
                self::F_THUMBNAIL_UPLOAD_MODE_BOTH,
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_BOTH),
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_BOTH . '_info')
            )
            ->withOption(
                self::F_THUMBNAIL_UPLOAD_MODE_TIMEPOINT,
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_TIMEPOINT),
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_TIMEPOINT . '_info')
            )
            ->withOption(
                self::F_THUMBNAIL_UPLOAD_MODE_FILE,
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_FILE),
                $this->txt(self::F_THUMBNAIL_UPLOAD_MODE_FILE . '_info')
            )
            ->withRequired(true)
            ->withValue($selected_mode);

        // Main enable upload thumbnail option.
        $inputs = [];
        $optional_group = $this->ui_factory->input()->field()->optionalGroup(
            $dependant_fields,
            $this->txt(
                self::F_THUMBNAIL_UPLOAD_ENABLED
            ),
            $this->txt(
                self::F_THUMBNAIL_UPLOAD_ENABLED . '_info'
            ),
        );

        if (PluginConfig::getConfig(PluginConfig::F_THUMBNAIL_UPLOAD_ENABLED) == false) {
            $optional_group = $optional_group->withValue(null);
        }
        $inputs[self::F_THUMBNAIL_UPLOAD_ENABLED] = $optional_group;

        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
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
}
