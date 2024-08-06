<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\LegacyHelpers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @deprecated
 */
trait TranslatorTrait
{
    /**
     * @deprecated use $this->>plugin->txt() instead
     */
    public function translate(
        string $key,
        string $module = "",
        array $placeholders = [],
        bool $plugin = true,
        string $lang = "",
        string $default = "MISSING %s"
    ): string {
        if (!empty($module)) {
            $key = $module . "_" . $key;
        }

        global $DIC;

        $lng = $DIC->language();
        $plugin_object = \ilOpenCastPlugin::getInstance();

        if ($plugin) {
            $lng->loadLanguageModule($plugin_object->getPrefix());

            $txt = $lng->exists($plugin_object->getPrefix() . "_" . $key) ? $lng->txt(
                $plugin_object->getPrefix() . "_" . $key
            ) : "";
        } else {
            if (!empty($module)) {
                $lng->loadLanguageModule($module);
            }

            $txt = $lng->exists($key) ? $lng->txt($key) : "";
        }

        if (!(empty($txt) || $txt === "MISSING" || str_starts_with((string) $txt, "MISSING "))) {
            try {
                $txt = vsprintf($txt, $placeholders);
            } catch (\Exception $ex) {
                throw new \Exception(
                    "Please use the placeholders feature and not direct `sprintf` or `vsprintf` in your code!",
                    $ex->getCode(),
                    $ex
                );
            }
        } elseif ($default !== null) {
            try {
                $txt = sprintf($default, $key);
            } catch (\Exception $ex) {
                throw new \Exception(
                    "Please use only one placeholder in the default text for the key!",
                    $ex->getCode(),
                    $ex
                );
            }
        }

        return str_replace("\\n", "\n", $txt);
    }
}
