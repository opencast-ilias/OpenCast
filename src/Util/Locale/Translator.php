<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Locale;

use srag\Plugins\Opencast\Container\Container;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Translator
{
    use LocaleTrait;

    private \ilOpenCastPlugin $plugin;

    public function __construct(Container $container)
    {
        $this->plugin = $opencastContainer[\ilOpenCastPlugin::class];
    }
}
