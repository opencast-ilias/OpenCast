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
    private string $prefix;

    public function __construct(Container $container)
    {
        $this->plugin = $container[\ilOpenCastPlugin::class];
        $this->prefix = $this->plugin->getPrefix();
    }

    public function translate(string $key): string
    {
        return $this->getLocaleString($key);
    }

    public function has(string $key): bool
    {
        $plugin_translation = $this->plugin->txt($key);

        return !empty($plugin_translation) && $plugin_translation !== ('-' . $this->prefix . '_' . $key . '-');
    }
}
