
import {
  PopUpButtonPlugin,
  PaellaCorePlugins,
  createElementWithHtmlText,
  translate,
  utils
} from '@asicupv/paella-core';

import DOMPurify from 'dompurify';

import TranscriptionsIcon from '../icons/transcriptions.svg';
import './css/transcriptions_plugin.css';

/**
* TranscriptionsPlugin
* Customized version of Opencast TranscriptionsPlugin for ILIAS Opencast Plugin
* @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/
export default class TranscriptionsPlugin extends PopUpButtonPlugin {
  getPluginModuleInstance() {
    return PaellaCorePlugins.Get();
  }

  get name() {
      return "org.ilias.paella.transcriptionsPlugin";
  }

  get side() {
    return "right";
  }

  get popUpType() {
    return 'modal';
  }

  get closeActions() {
    return {
      clickOutside: false,
      closeButton: true
    };
  }

  get customPopUpClass() {
    return 'transcription-plugin-popup';
  }

  get menuTitle() {
    return 'Transcriptions';
  }

  async isEnabled() {
    const enabled = await super.isEnabled();
    this.transcriptions = this?.player?.videoManifest?.transcriptions?.filter(t => t?.text != '') || [];
    return enabled && this.transcriptions.length > 0;
  }

  async load() {
    this.icon = this.player.getCustomPluginIcon(this.name, 'buttonIcon') || TranscriptionsIcon;
  }

  rebuildList(search = '') {
    const { videoContainer } = this.player;
    this._transcriptionsContainer.innerHTML = '';
    this.transcriptions
    .filter(t => { // Trim
      if (videoContainer.isTrimEnabled) {
        return (t.time > videoContainer.trimStart) && (t.time < videoContainer.trimEnd);
      }
      return true;
    })
    .filter(t => { // Search
      if (search !== '') {
        const searchExp = search.split(' ').map(s => `(?:${s})`).join('|');
        const re = new RegExp(searchExp, 'i');
        return re.test(t.text);
      }
      return true;
    })
    .forEach(t => {
      const id = `transcriptionItem${t.id}`;
      const trimmingOffset = videoContainer.isTrimEnabled ? videoContainer.trimStart : 0;
      const instant = t.time - trimmingOffset;
      const transcriptionItem = createElementWithHtmlText(
        `<li>
          <img id="${id}" src="${t.thumb}" alt="${t.text}"/>
          <div class="details">
            <span class="timepoint">${utils.secondsToTime(instant)}</span>
            <span>${t.text}</span>
          </div>
        </li>`,
        this._transcriptionsContainer
      );
      transcriptionItem.addEventListener('click', async evt => {
        const trimmingOffset = videoContainer.isTrimEnabled ? videoContainer.trimStart : 0;
        this.player.videoContainer.setCurrentTime(t.time - trimmingOffset);
        evt.stopPropagation();
      });
    });
  }

  debounce(func, delay) {
    let timer;
    return function(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  async getContent() {
    let mainDiv = document.createElement('div');
    mainDiv.classList.add('transcriptions-container');
    let searchInput = document.createElement('input');
    searchInput.setAttribute('type', 'search');
    searchInput.setAttribute('placeholder', translate('Search'));
    searchInput.addEventListener(
      'click',
      evt => evt.stopPropagation()
    );

    const debouncedRebuild = this.debounce((val) => this.rebuildList(val), 300);

    searchInput.addEventListener(
      'keyup',
      evt => {
        evt.stopPropagation();
        let rawText = evt.target.value.trim();
        let cleanText = DOMPurify.sanitize(rawText);
        debouncedRebuild(cleanText);
      }
    );
    mainDiv.appendChild(searchInput);
    let list = document.createElement('ul');
    list.classList.add('transcriptions-list');
    this._transcriptionsContainer = list;
    mainDiv.appendChild(list);
    this.rebuildList();
    return mainDiv;
  }

  preload() {
    console.log("ILIAS-Paella: TranscriptionsPlugin plugin loading...");
  }
}
