'use strict';
import $ from "jquery";
import { OpencastPaellaPlayer } from '@asicupv/paella-opencast-core';
import { Events, utils } from '@asicupv/paella-core';
import { basicPlugins } from '@asicupv/paella-basic-plugins';
import { slidePlugins } from '@asicupv/paella-slide-plugins';
import { zoomPlugins } from '@asicupv/paella-zoom-plugin';
import { userTrackingPlugins } from '@asicupv/paella-user-tracking';
import { videoPlugins } from '@asicupv/paella-video-plugins';
import { extraPlugins } from '@asicupv/paella-extra-plugins';
import { webglPlugins } from '@asicupv/paella-webgl-plugins';
import{ opencastPlugins } from '@asicupv/paella-opencast-plugins';
import TranscriptionsPlugin from './plugins/org.ilias.paella.transcriptionsPlugin.js';
import LiveStreamingButtonIndicator from './plugins/org.ilias.paella.liveStreamingButtonIndicator.js';
import localDictionaries from "./lang/registery";
import forwardIcon from './resources/forwardIcon.svg';
import backwardIcon from './resources/backwardIcon.svg';

import '@asicupv/paella-core/paella-core.css';
import '@asicupv/paella-basic-plugins/paella-basic-plugins.css';
import '@asicupv/paella-slide-plugins/paella-slide-plugins.css';
import '@asicupv/paella-zoom-plugin/paella-zoom-plugin.css';
import '@asicupv/paella-extra-plugins/paella-extra-plugins.css';
import '@asicupv/paella-opencast-core/paella-opencast-core.css';

const { getUrlParameter } = utils;

const loadVideoManifestFunction = () => {
    if (typeof il !== 'undefined') {
        return il.Opencast.Paella.player.data;
    }
    return window.PaellaPlayer.default.data;
};

const noop = () => {};

const getVideoIdFunction = (config, player) => {
    player.log.info("Using ILIAS custom getVideoIdFunction to get the videoId.");
    return il?.Opencast?.Paella?.player?.data?.metadata?.videoid || getUrlParameter("eid") ||
        window?.PaellaPlayer?.default?.data?.metadata?.videoid;
}

/**
 * PaellaPlayer
 *
 * Version 8
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default {

    data: [],

    config: {
        paella_config_file: '',
        paella_config_livestream_type: '',
        paella_config_livestream_buffered: false,
        paella_config_resources_path: '',
        paella_config_fallback_captions: '',
        paella_config_fallback_langs: '',
        paella_config_info: '',
        paella_preview_fallback: '',
        paella_config_is_warning: false,
        is_live_stream: false,
        event_start: 0,
        event_end: 0,
        check_script_hls: '',
        paella_theme: '',
        paella_theme_live: '',
        paella_theme_info: '',
        prevent_video_download: false,
    },

    caption_enabled: true,

    status: 'playing',

    // 10 minute buffer before the event is considered 'started', to avoid wrong status message for
    // events started too late
    event_start_buffer: 60 * 10,

    paella: null,

    binding_delay: null,

    init: function(data, config) {

        if (config.paella_config_is_warning) {
            console.warn(config.paella_config_info);
        } else {
            console.log(config.paella_config_info);
        }

        this.data = data;
        this.config = config;
        this.config.user_default_language = navigator?.language?.substring(0,2);

        this.generateCaptionText();

        this.initPaella();
        if (this.config.is_live_stream === true) {
            this.hasWorkingStream().then(stream_available => {
                if (stream_available) {
                    this.loadPlayer();
                    this.checkStreamStatus();
                } else {
                    this.triggerOverlays();
                    this.checkAndLoadLive();
                }
            });
        } else {
            this.loadPlayer();
        }

        window.addEventListener('message', function(e) {
            // message passed can be accessed in "data" attribute of the event object
            let scroll_height = e.data;
            $('#srchat_iframe').attr('height', scroll_height + 'px');
        } , false);
    },

    generateCaptionText: function() {
        if (this.data?.captions) {
            for (const captionIndex in this.data.captions) {
                const lang_code = this.data.captions[captionIndex].lang;
                let iso_639_1_lang_code = lang_code;
                let lang_name = lang_code;
                try {
                    iso_639_1_lang_code = lang_code.split('-', 2)?.[0].trim() ?? lang_code;
                    const options = {
                        type: "language",
                        languageDisplay: "standard"
                    };
                    const display_name_obj = new Intl.DisplayNames([this.config.user_default_language], options);
                    if (display_name_obj) {
                        lang_name = display_name_obj.of(iso_639_1_lang_code);
                    }
                } catch (e) {}
                if (lang_name) {
                    let text = lang_name;
                    if (this.data.captions[captionIndex]?.text) {
                        text += '  ' + this.data.captions[captionIndex].text;
                    }
                    this.data.captions[captionIndex].text = text;
                }
            }
        }
    },

    checkPreview: async function() {
        let preview = this.data?.metadata?.preview;
        if (preview) {
            var http = new XMLHttpRequest();
            http.open('GET', preview, false);
            http.send();
            let accessible = http.status != 403 && http.status != 404;
            if (!accessible && this.config?.paella_preview_fallback) {
                this.data.metadata.preview = this.config.paella_preview_fallback;
            }
        } else {
            this.data.metadata.preview = this.config.paella_preview_fallback;
        }
    },

    initPaella: function() {
        this.checkPreview();
        this.paella = new OpencastPaellaPlayer('playerContainer', {
            configResourcesUrl: this.config.paella_config_resources_path,
            configUrl: this.config.paella_config_file,
            getManifestUrl: noop,
            getManifestFileUrl: noop,
            loadVideoManifest: loadVideoManifestFunction,
            plugins: [
                ...basicPlugins,
                ...slidePlugins,
                ...zoomPlugins,
                ...userTrackingPlugins,
                ...videoPlugins,
                ...webglPlugins,
                ...extraPlugins,
                ...opencastPlugins,
                TranscriptionsPlugin,
                LiveStreamingButtonIndicator
            ],
            getVideoId: getVideoIdFunction
        });
    },

    loadPlayer: function() {
        $('#overlay_live_waiting').hide();
        this.filterStreams().then(async () => {
            try {
                await this.loadTheme();
                $('#overlay_loading').remove();
                await this.paella.loadManifest();
                this.binding_delay = setTimeout(() => {
                    this.bindPaellaEvents();
                }, 500);

                this.adjustForwardBackwardCustomIcons();
                console.log("ILIAS-Paella: Initialization done");
            } catch (error) {
                console.error(error);
            }
        });
    },

    adjustForwardBackwardCustomIcons: function() {
        let forwardTime = this.paella?.config?.plugins?.['es.upv.paella.forwardButtonPlugin']?.time;
        if (forwardTime && forwardIcon) {
            let filteredForwardIcon = forwardIcon.replace('{{SECONDS}}', forwardTime);
            this.paella.addCustomPluginIcon("es.upv.paella.forwardButtonPlugin", "forwardIcon", filteredForwardIcon);
        }

        let backwardTime = this.paella?.config?.plugins?.['es.upv.paella.backwardButtonPlugin']?.time;
        if (backwardTime && backwardIcon) {
            let filteredBackwardIcon = backwardIcon.replace('{{SECONDS}}', backwardTime);
            this.paella.addCustomPluginIcon("es.upv.paella.backwardButtonPlugin", "backwardIcon", filteredBackwardIcon);
        }
    },

    loadTheme: async function() {
        let theme_url = this.config.paella_theme;
        if (this.config.is_live_stream) {
            theme_url = this.config.paella_theme_live;
        }
        if (theme_url !== 'opencast') {
            await this.paella.skin.loadSkin(theme_url);
        } else {
            // In case we don't pass any custom Theme, we use opencast theme!
            await this.paella.applyOpencastTheme();
        }
        console.log(this.config.paella_theme_info);
    },

    bindPaellaEvents: function() {
        this.paella.bindEvent(
            Events.PLAY,
            () => {
                setTimeout(() => this.enableDefaultCaption(), 250);
            },
            false
        );
        this.paella.bindEvent(
            Events.MANIFEST_LOADED,
            () => this.handlePaellaLanguages(),
            false
        );
        this.paella.bindEvent(
            Events.PLAYER_LOADED,
            () => {
                this.handleLiveAttributes();

                // Apply prevent video download
                if (this.config?.prevent_video_download) {
                    this.paella.videoContainer.streamProvider.players.forEach(player => {
                        player.video?.addEventListener('contextmenu', (e) => {
                            e.preventDefault();
                        });
                    });
                }
            },
            false
        );
        this.paella.bindEvent(
            Events.CAPTIONS_DISABLED,
            () => this.caption_enabled = false,
            false
        );
        this.paella.bindEvent(
            Events.CAPTIONS_ENABLED,
            () => this.caption_enabled = true,
            false
        );
        if (this.binding_delay != null) {
            clearTimeout(this.binding_delay);
        }
    },

    handleLiveAttributes: function() {
        if (!this.config.is_live_stream || this.config.paella_config_livestream_buffered) {
            return;
        }
        if (this.paella?.playbackBar?.progressIndicator?.container) {
            $(this.paella.playbackBar.progressIndicator.container).hide();
        }
    },

    enableDefaultCaption: async function() {
        const captionsCanvas = await this.paella.captionsCanvas;
        // If the currentCaptions already has value, means the video has already been played and the caption is set before.
        if (captionsCanvas.currentCaptions !== null || this.caption_enabled === false) {
            return;
        }
        let defaultCaption = '';
        // Priority to users browser language.
        let userDefaultLanguage = this.config.user_default_language;
        let hasCaption = captionsCanvas.getCaptions({
            lang: userDefaultLanguage
        });
        if (hasCaption) {
            defaultCaption = userDefaultLanguage;
            console.log(`Setting default caption to browser language: ${userDefaultLanguage}`);
        } else {
            let configFallbackCaptions = this.config?.paella_config_fallback_captions;
            for (const index in configFallbackCaptions) {
                let fallbackCaption = configFallbackCaptions[index];
                hasCaption = captionsCanvas.getCaptions({
                    lang: fallbackCaption
                });
                if (hasCaption) {
                    defaultCaption = fallbackCaption;
                    console.log(`Setting fallback caption to: ${fallbackCaption}`);
                    break;
                }
            }
        }

        if (defaultCaption !== '') {
            this.caption_enabled = true;
            captionsCanvas.enableCaptions({
                lang: defaultCaption
            });
        }
    },

    handlePaellaLanguages: function() {
        let paellaLang = '';
        // Install fallback languages.
        if (this.config?.paella_config_fallback_langs) {
            for (const fallbackLang of this.config.paella_config_fallback_langs) {
                if (!['en', 'es'].includes(fallbackLang)) {
                    this.installDictionary(fallbackLang);
                }
            }
        }
        // Get dictionaries to check availability of languages.
        let paellaDictionaries = this.paella.getDictionaries();

        // First: users browser language.
        let userDefaultLanguage = this.config.user_default_language;
        if (Object.keys(paellaDictionaries).includes(userDefaultLanguage)) {
            paellaLang = userDefaultLanguage;
        }

        if (paellaLang == '') {
            for (const fl of this.config.paella_config_fallback_langs) {
                if (Object.keys(paellaDictionaries).includes(fl)) {
                    paellaLang = fl;
                    break;
                }
            }
        }

        // In case the paellaLang has value after all prevoius evaluations then set the language,
        // otherwise go for what language it is shipped with.
        if (paellaLang != '') {
            this.paella.setLanguage(paellaLang);
        }
    },

    installDictionary: function(confLang) {
        for (const lang in localDictionaries) {
            if (lang === confLang) {
                let dictionary = localDictionaries[lang];
                if (Object.entries(dictionary).length > 0) {
                    this.paella.addDictionary(lang, dictionary);
                }
                break;
            }
        }
    },

    reloadPlayer: function() {
        this.paella.pause();
        this.paella = null;
        $('#playerContainer').empty();
        this.initPaella();
        let i = setInterval(function() {
            if (typeof this.paella == 'object') {
                this.loadPlayer();
                this.paella.play();
                clearInterval(i);
            }
        }, 500);
    },

    triggerOverlays: function() {
        let ts = Math.round(new Date().getTime() / 1000);
        if (ts < (this.config.event_start + this.event_start_buffer)) {
            this.showOverlay('waiting');
        } else if (ts > this.config.event_end) {
            this.showOverlay('over');
        } else {
            this.showOverlay('interrupted');
        }
    },

    showOverlay: function(status) {
        this.hideOverlays();
        $('#overlay_live_' + status).show();
    },

    hideOverlays: function() {
        $('#overlay_live_waiting').hide();
        $('#overlay_live_interrupted').hide();
        $('#overlay_live_over').hide();
    },

    isStreamWorking: async function(url) {
        let working = (await $.get(this.config.check_script_hls + "?url=" + url + "&livestream_type=" + this.config.paella_config_livestream_type) === 'true');
        return working;
    },

    /**
     * check for working streams
     * @returns {Promise<boolean>}
     */
    hasWorkingStream: async function() {
        var working_stream_found = false;
        for (const stream of this.data.streams) {
            if (!working_stream_found) {
                let src = this.config.paella_config_livestream_buffered ? stream.sources.hls[0].src : stream.sources.hlsLive[0].src;
                working_stream_found = await this.isStreamWorking(src);
            }
        }
        return working_stream_found;
    },

    /**
     * checks if the live stream is available yet
     * (this check is executed before event start)
     * @returns {Promise<void>}
     */
    checkAndLoadLive: async function() {
        var i = setInterval(async () => {
            if (await this.hasWorkingStream()) {
                this.hideOverlays();
                this.loadPlayer();
                clearInterval(i);
                this.checkStreamStatus();
            } else {
                console.log('no working stream found - try again in 5 seconds');
            }
        }, 5000)
    },

    /**
     * starts a loop which checks the availability of the streams
     * and shows the appropriate overlays, or hides them and reloads
     * the player, respectively
     */
    checkStreamStatus: function() {
        let i = null;
        let f = async () => {
            console.log('check stream status');
            var ts = Math.round(new Date().getTime() / 1000);
            if (!(await this.hasWorkingStream())) {
                if (ts >= this.config.event_end) {
                    this.status = 'over';
                } else if (ts < (this.config.event_start + this.event_start_buffer)) {
                    this.status = 'waiting';
                } else {
                    this.status = 'interrupted';
                }
                this.hideOverlays();
                this.showOverlay(this.status);
                this.paella.pause();
                clearInterval(i);
                if (this.status === 'over') {
                    $('#playerContainer').empty();
                } else {
                    i = setInterval(f, 2000);
                }
            } else {
                if (this.status !== 'playing') {
                    this.reloadPlayer();
                }
                this.status = 'playing';
                this.hideOverlays();
                clearInterval(i);
                i = setInterval(f, 20000);
            }
        };
        i = setInterval(f, 20000)
    },

    filterStreams: async function() {
        if (this.config.is_live_stream) {
            for (const streamKey in this.data.streams) {
                let src = this.config.paella_config_livestream_buffered ?
                    this.data.streams[streamKey].sources.hls[0].src :
                    this.data.streams[streamKey].sources.hlsLive[0].src;
                if (this.data.streams.hasOwnProperty(streamKey) &&
                    (!(await this.isStreamWorking(src)))) {
                    this.data.streams.splice(streamKey, 1);
                }
            }
        }
    },

    testLivePaella: async function(containerId, configUrl, configResourcesUrl, previewUrl, themeUrl, hlsUrl = '', withBuffer = false) {
        if (hlsUrl === '') {
            hlsUrl = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';
        }
        let hlsObj = [
            {src: hlsUrl, mimetype: 'application/x-mpegURL'}
        ];
        let stream = {
            content: 'presenter',
            sources: {}
        };
        if (withBuffer) {
            stream.sources.hls = hlsObj;
        } else {
            stream.sources.hlsLive = hlsObj;
        }
        this.data = {
            streams: [
                stream
            ],
            metadata: {title: 'test live', preview: previewUrl}
        };
        if (this.paella) {
            this.paella.pause();
            this.paella.unload();
            this.paella = null;
            $('#' + containerId).empty();
        }
        this.paella = new OpencastPaellaPlayer(containerId, {
            configUrl: configUrl,
            configResourcesUrl: configResourcesUrl,
            getManifestUrl: noop,
            getManifestFileUrl: noop,
            loadVideoManifest: loadVideoManifestFunction,
            plugins: [
                ...basicPlugins,
                ...slidePlugins,
                ...zoomPlugins,
                ...userTrackingPlugins,
                ...videoPlugins,
                ...webglPlugins,
                ...extraPlugins,
                ...opencastPlugins,
                TranscriptionsPlugin,
                LiveStreamingButtonIndicator
            ],
        });
        if (themeUrl != '') {
            await this.paella.skin.loadSkin(themeUrl);
        } else {
            await this.paella.applyOpencastTheme();
        }
        await this.paella.loadManifest();
        console.log("Initialization done");
        setTimeout(() => {
            this.paella.bindEvent(
                Events.PLAYER_LOADED,
                () => {
                    if (!withBuffer) {
                        if (this.paella?.playbackBar?.progressIndicator?.container) {
                            $(this.paella.playbackBar.progressIndicator.container).hide();
                        }
                    }
                },
                false
            );
        }, 500);
    }
}
