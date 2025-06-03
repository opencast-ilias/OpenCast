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
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use ILIAS\UI\Component\Component;
use srag\Plugins\Opencast\Views\ViewElement;
use srag\Plugins\Opencast\UI\Integration\Integration;
use ILIAS\Data\URI;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Display implements ViewElement
{

    public function __construct(
        private Integration $ui_integration,
        private string $series_id,
        private URI $current_url,
        private URI $target_url,
    ) {
    }

    public function get(): Component|array
    {
        return iterator_to_array(
            $this->ui_integration->series()->asEntityListInPanel(
                $this->series_id,
                //$this->current_url,
                //$this->target_url
            )
        );
    }

}
