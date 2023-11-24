<?php

declare(strict_types=1);

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

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\Template;
use ILIAS\DI\Exceptions\Exception;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @deprecated
 */
trait OutputTrait
{
    /**
     * @deperecated
     */
    public function getHTML($value): string
    {
        global $DIC;
        \ilOpenCastPlugin::getInstance();

        if (is_array($value)) {
            $html = "";
            foreach ($value as $gui) {
                $html .= $this->getHTML($gui);
            }
        } else {
            switch (true) {
                // HTML
                case (is_string($value)):
                    $html = $value;
                    break;

                // Component instance
                case ($value instanceof Component):
                    $html = $DIC->ctrl()->isAsynch() ? $DIC->ui()->renderer()->renderAsync($value) : $DIC->ui(
                    )->renderer()->render($value);
                    break;

                // ilTable2GUI instance
                case ($value instanceof \ilTable2GUI):
                    // Fix stupid broken ilTable2GUI (render has only header without rows)
                    $html = $value->getHTML();
                    break;

                // GUI instance
                case method_exists($value, "render"):
                    $html = $value->render();
                    break;
                case method_exists($value, "getHTML"):
                    $html = $value->getHTML();
                    break;

                // Template instance
                case ($value instanceof \ilTemplate):
                case ($value instanceof Template):
                    $html = $value->get();
                    break;

                // Not supported!
                default:
                    throw new Exception("Class " . get_class($value) . " is not supported for output!");
            }
        }

        return (string) $html;
    }

    /**
     * @deprecated
     */
    public function output($value, bool $show = false, bool $main_template = true): void
    {
        global $DIC;
        $html = $this->getHTML($value);

        if ($DIC->ctrl()->isAsynch()) {
            echo $html;

            exit;
        } else {
            if ($main_template) {
                $DIC->ui()->mainTemplate()->loadStandardTemplate();
            }

            $DIC->ui()->mainTemplate()->setLocator();

            if (!empty($html)) {
                $DIC->ui()->mainTemplate()->setContent($html);
            }

            if ($show) {
                $DIC->ui()->mainTemplate()->printToStdout();
            }
        }
    }

    /**
     * @deperecated
     */
    public function outputJSON($value): void
    {
        switch (true) {
            case (is_string($value)):
            case (is_int($value)):
            case (is_float($value)):
            case (is_bool($value)):
            case (is_array($value)):
            case ($value instanceof \stdClass):
            case ($value === null):
            case ($value instanceof \JsonSerializable):
                $value = json_encode($value);

                header("Content-Type: application/json; charset=utf-8");

                echo $value;

                exit;

            default:
                throw new \Exception(get_class($value) . " is not a valid JSON value!");
        }
    }
}
