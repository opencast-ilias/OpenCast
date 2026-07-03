import { ButtonPlugin, PaellaCorePlugins } from '@asicupv/paella-core';

import redDotIcon from '../icons/red-dot.svg';

/**
 * @deprecated since Paella Player 8
 */
export default class LiveStreamingButtonIndicator extends ButtonPlugin {
    getPluginModuleInstance() {
        return PaellaCorePlugins.Get();
    }

    get name() {
        return "org.ilias.paella.liveStreamingButtonIndicator";
    }

    get side() {
        return "left";
    }

    async isEnabled() {
        return this.config?.enabled;
    }

    getAriaLabel() {
        return "Livestream";
    }

    getDescription() {
        return this.getAriaLabel();
    }

    get className() {
        return "ilias-livestream-button";
    }

    get titleSize() {
        return "large";
    }

    async load() {
        this.title = "Livestream";
        this.icon = redDotIcon;
    }

    get interactive() {
        return false;
    }

    get dynamicWidth() {
        return true;
    }

    preload() {
        console.log("ILIAS-Paella: LiveStreamingButtonIndicator plugin loading...");
    }
}
