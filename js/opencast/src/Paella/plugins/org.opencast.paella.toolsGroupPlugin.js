import { ButtonGroupPlugin } from 'paella-core';

import MenuIcon from '../icons/settings-icon.svg';

export default class ToolsGroupPlugin extends ButtonGroupPlugin {
    async load() {
        this.icon = this.player.getCustomPluginIcon(this.name, 'buttonIcon') || MenuIcon;
    }
}
