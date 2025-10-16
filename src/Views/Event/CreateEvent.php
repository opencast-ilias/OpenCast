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

namespace srag\Plugins\Opencast\Views\Event;

use srag\Plugins\Opencast\Views\ViewElement;
use ILIAS\UI\Component\Component;
use srag\Plugins\Opencast\Container\Init;
use srag\Plugins\Opencast\UI\EventFormBuilder;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class CreateEvent implements ViewElement
{
    private EventFormBuilder $event_form_builder;

    public function __construct(
        private string $form_action,
        private bool $with_terms_of_use,
        private int $obj_id = 0,
        private bool $as_admin = false
    ) {
        $container = Init::init();
        $this->event_form_builder = $container->legacy()->event_form_builder();
    }

    public function get(): Component|array
    {
        return $this->event_form_builder->upload(
            $this->form_action,
            $this->with_terms_of_use,
            $this->obj_id,
            $this->as_admin
        );
    }

}
