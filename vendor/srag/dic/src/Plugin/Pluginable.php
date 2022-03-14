<?php

namespace srag\DIC\OpenCast\Plugin;

/**
 * Interface Pluginable
 *
 * @package srag\DIC\OpenCast\Plugin
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
