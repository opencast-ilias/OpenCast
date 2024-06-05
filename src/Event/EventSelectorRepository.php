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

namespace srag\Plugins\Opencast\Event;

use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Container\Init;
use ILIAS\UI\Implementation\Component\Table\Presentation;
use srag\Plugins\Opencast\Util\Locale\Translator;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventSelectorRepository
{
    private Container $container;
    private \ILIAS\UI\Factory $ui_factory;
    private Translator $translator;

    public function __construct()
    {
        global $DIC;
        $this->container = Init::init($DIC);
        $this->translator = $this->container->get(Translator::class);
        $this->ui_factory = $DIC->ui()->factory();
    }

    public function table(): Presentation
    {
        return $this->ui_factory->table()->presentation(
            $this->translator->getLocaleString('events')
        );
    }
}
