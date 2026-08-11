import {
    PopUpButtonPlugin,
    createElementWithHtmlText,
    bindEvent,
    Events,
    Paella,
    PaellaCorePlugins
} from '@asicupv/paella-core';

import DOMPurify from 'dompurify';

import './css/findcaptions_plugin.css';

import searchIcon from '../icons/binoculars.svg';

/**
 * FindCaptionsPlugin
 * Customized version of Opencast FindCaptionsPlugin for ILIAS Opencast Plugin.
 * The concept and implementation of this plugin are similar to the main FindCaptionsPlugin from the Paella ecosystem, with the only notable difference being the way the search input HTML element is rendered.
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/
export default class FindCaptionsPlugin extends PopUpButtonPlugin {
    getPluginModuleInstance() {
        return PaellaCorePlugins.Get();
    }

    get name() {
        return "org.ilias.paella.findCaptionsPlugin";
    }

    async getContent() {
        const placeholderText = this.player.translate("Search");
        const content = createElementWithHtmlText(`<div class="captions-search-container"></div>`);

        this._resultsContainer = createElementWithHtmlText('<div class="search-results"></div>', content);

        const input = document.createElement('input');
        input.setAttribute('type', 'search');
        input.setAttribute('placeholder', this.player.translate('Search'));
        input.addEventListener(
            'click',
            evt => evt.stopPropagation()
        );

        content.appendChild(input);

        const browserLanguage = navigator.language.substring(0,2);
        const isCurrentLanguage = (lang) => {
            // If there are some captions enabled, compare with this language
            if (this.player.captionsCanvas.currentCaptions) {
                return lang === this.player.captionsCanvas.currentCaptions.language;
            }

            // Otherwise, compare with the browser language
            return lang === browserLanguage;
        }

        const showAllCaptions = () => {
            let captions = null;
            this.captions.some(lang => {
                if (isCurrentLanguage(lang.language)) {
                    captions = lang;
                }
            });
            if (!captions) {
                captions = this.captions[0];
            }

            this._cueElements = [];
            captions && captions.cues.forEach(cue => {
                const cueElem = createElementWithHtmlText(`<p class="result-item">${cue.startString}: ${cue.captions[0]}</p>`, this._resultsContainer);
                cueElem._cue = cue;
                cueElem.addEventListener('click', async evt => {
                    const time = evt.target._cue.start;
                    await this.player.videoContainer.setCurrentTime(time);
                    evt.stopPropagation();
                });
                this._cueElements.push(cueElem);
            })
        }

        showAllCaptions();

        let searchTimer = null;
        input.addEventListener('keyup', (evt) => {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            const rawText = evt.target.value.trim();
            const cleanText = DOMPurify.sanitize(rawText);
            this._resultsContainer.innerHTML = "";
            const currentLanguage = this.player.getLanguage();
            searchTimer = setTimeout(() => {
                const results = {};
                this.captions.forEach(lang => {
                    lang.cues.forEach(cue => {
                        if (cue.captions.find(cap => (new RegExp(cleanText,"i")).test(cap))) {
                            results[cue.startString] = results[cue.startString] || { cue, text: {} }
                            results[cue.startString].text[lang.language] = cue.captions;
                        }
                    })
                });

                this._cueElements = [];
                for (const timeString in results) {
                    const res = results[timeString];
                    const text = res.text[currentLanguage] || res.text[Object.keys(res.text)[0]];
                    const resultElem = createElementWithHtmlText(`<p class="result-item">${res.cue.startString}: ${text[0]}</p>`, this._resultsContainer);
                    resultElem._cue = res.cue;
                    resultElem.addEventListener('click', async (evt) => {
                        const time = evt.target._cue.start;
                        await this.player.videoContainer.setCurrentTime(time);
                        evt.stopPropagation();
                    });
                    this._cueElements.push(resultElem);
                }
                if (Object.keys(results).length === 0 && cleanText !== '') {
                    createElementWithHtmlText(`<p>${this.player.translate("No results found")}</p>`, this._resultsContainer);
                }
                else if (cleanText === '') {
                    showAllCaptions();
                }
                searchTimer = null;
            }, 1000);

            evt.stopPropagation();
        });

        // If there is no text in search field, scroll to current caption on time update
        if (!this._timeupdateEvent) {
            this._timeupdateEvent = async evt => {
                if (input.value === "" && this._cueElements?.length) {
                    this._cueElements.forEach(elem => {
                        if (elem._cue.start<=evt.currentTime && elem._cue.end>=evt.currentTime) {
                            elem.classList.add('current');
                            const elemPosTop = elem.offsetTop - this._resultsContainer.scrollTop;
                            if (elemPosTop<0 || elemPosTop>this._resultsContainer.clientHeight) {
                                this._resultsContainer.scrollTo({ top: elem.offsetTop - 20 });
                            }
                        }
                        else {
                            elem.classList.remove('current');
                        }
                    });
                }
            }
            this.player.bindEvent(Events.TIMEUPDATE, this._timeupdateEvent, true);
        }

        // Force reload content
        setTimeout(() => this.refreshContent = true, 10);
        return content;
    }

    get popUpType() {
        return "no-modal";
    }

    get captions() {
        return this.player.captionsCanvas.captions;
    }

    get customPopUpClass() {
        return "find-captions";
    }

    async load() {
        this.icon = this.player.getCustomPluginIcon(this.name,"findCaptionsIcon") || searchIcon;
        this._captionsCanvas = this.player.captionsCanvas;

        if (this.captions.length === 0) {
            this.disable();
        }

        bindEvent(this.player, Events.CAPTIONS_CHANGED, () => {
            if (this.captions.length > 0) {
                this.enable();
            }
        })
    }

    preload() {
        console.log("ILIAS-Paella: FindCaptionsPlugin plugin loading...");
    }
}
