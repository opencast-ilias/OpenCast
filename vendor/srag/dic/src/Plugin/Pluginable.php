<?php

namespace srag\DIC\OpencastObject\Plugin;

/**
 * Interface Pluginable
 *
 * @package srag\DIC\OpencastObject\Plugin
 */
interface Pluginable
{

    /**
     * @return PluginInterface
     */
    public function getPlugin() : PluginInterface;


    /**
     * @param PluginInterface $plugin
     *
     * @return static
     */
    public function withPlugin(PluginInterface $plugin)/*: static*/ ;
}
