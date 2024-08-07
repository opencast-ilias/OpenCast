
import {
  PopUpButtonPlugin,
  createElementWithHtmlText,
  translate,
  utils
  } from 'paella-core';


import TranscriptionsIcon from '../icons/transcriptions.svg';
import './css/transcriptions_plugin.css';

/**
* TranscriptionsPlugin
* Customized version of Opencast TranscriptionsPlugin for ILIAS Opencast Plugin
* @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/
export default class TranscriptionsPlugin extends PopUpButtonPlugin {

  get moveable() {
    return true;
  }

  get resizeable() {
    return true;
  }

  get popUpType() {
    return 'no-modal';
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

  async getContent() {
    const container = createElementWithHtmlText('<div class="transcriptions-container"></div>');
    const searchContainer = createElementWithHtmlText(
      `<input type="search" placeholder="${translate('Search')}"></input>`,
      container
    );

    searchContainer.addEventListener(
      'click',
      evt => evt.stopPropagation()
    );

    searchContainer.addEventListener(
      'keyup',
      evt => {
        evt.stopPropagation();
        this.rebuildList(evt.target.value);
      }
    );

    const transcriptionsContainer = createElementWithHtmlText(
      `<ul class="transcriptions-list"></ul>`,
      container
    );
    this._transcriptionsContainer = transcriptionsContainer;
    this.rebuildList();
    return container;
  }
}
