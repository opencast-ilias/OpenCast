<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

declare(strict_types=1);

namespace srag\Plugins\Opencast\LegacyHelpers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @deperecated
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

        if (!(empty($txt) || $txt === "MISSING" || strpos($txt, "MISSING ") === 0)) {
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
