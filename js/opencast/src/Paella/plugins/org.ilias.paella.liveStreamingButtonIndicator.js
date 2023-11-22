import { ButtonPlugin } from 'paella-core';

import redDotIcon from '../icons/red-dot.svg';

export default class LiveStreamingButtonIndicator extends ButtonPlugin {
    get name() {
        return "org.ilias.paella.liveStreamingButtonIndicator";
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
}
