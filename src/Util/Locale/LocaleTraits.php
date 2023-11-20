<?php
declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Locale;

/**
 * Trait LocaleTrait
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
trait LocaleTrait
{
    /**
     * A translator function, which makes sure that strings go through pluign->txt method to get translated.
     * It uses the plugin property of the class or gets it from global container.
     * @param string $string string to translate
     * @param string $module
     * @param ?string $fallback the fallback string in case the translataion does not exist.
     *
     * @return string
     */
    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        $locale_string = $string;
        // Attaching module if any!
        if (!empty($module)) {
            $locale_string = $module . "_" . $string;
        }

        // Make sure plugin object is there!
        global $opencastContainer;
        $plugin = property_exists($this, 'plugin') ? $this->plugin : $opencastContainer[\ilOpenCastPlugin::class];

        // Performing the regular txt translation.
        $translation = $plugin->txt($locale_string);

        // If a fallback string is provided, we check for every scenario in which the txt method might return a value in case of not finding the original string. (backwards compatibility with ILIAS < 8)
        $start = substr($translation, 0, 1);
        $end = substr($translation, -1);
        if (((empty($translation) || $translation === "MISSING" || strpos($translation, "MISSING ") === 0) ||
            ($start == '-' && $end == '-' && strpos($translation, $locale_string) !== false)) && !empty($fallback)) {
            return $fallback;
        }

        return $translation;
    }
}
