'use strict';
import $ from "jquery";
import { Paella } from 'paella-core';
import { Events } from 'paella-core';
import getBasicPluginContext from 'paella-basic-plugins';
import getSlidePluginContext from 'paella-slide-plugins';
import getZoomPluginContext from 'paella-zoom-plugin';
import getUserTrackingPluginsContext from 'paella-user-tracking';
import localDictionaries from "./lang/registery";

const loadVideoManifestFunction = () => {
    if (typeof il !== 'undefined') {
        return il.Opencast.Paella.player.data;
    }
    return window.PaellaPlayer.default.data;
};

const noop = () => {};


/**
 * PaellaPlayer
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
        this.paella = new Paella('playerContainer', {
            configResourcesUrl: this.config.paella_config_resources_path,
            configUrl: this.config.paella_config_file,
            getManifestUrl: noop,
            getManifestFileUrl: noop,
            loadVideoManifest: loadVideoManifestFunction,
            customPluginContext: [
                require.context('./plugins', true, /\.js/),
                getBasicPluginContext(),
                getSlidePluginContext(),
                getZoomPluginContext(),
                getUserTrackingPluginsContext()
            ]
        });
        this.loadTheme();
        this.bindPaellaEvents();
    },

    loadTheme: async function() {
        let theme_url = this.config.paella_theme;
        if (this.config.is_live_stream) {
            theme_url = this.config.paella_theme_live;
        }
        await this.paella.skin.loadSkin(theme_url);
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
    },

    handleLiveAttributes: function() {
        if (!this.config.is_live_stream || this.config.paella_config_livestream_buffered) {
            return;
        }
        this.paella.playbackBar.progressIndicator.hideProgressTimer();
        this.paella.playbackBar.progressIndicator.hideProgressContainer();
        this.paella.playbackBar.progressIndicator.hideTimeLine();
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

    loadPlayer: function() {
        $('#overlay_live_waiting').hide();
        this.filterStreams().then(() => {
            this.paella.loadManifest()
                .then(() => {
                    console.log("Initialization done");
                })
                .catch(e => console.error(e));
        });
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

    testLivePaella: async function(containerId, configUrl, previewUrl, themeUrl, hlsUrl = '', withBuffer = false) {
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
            this.paella = null;
            $('#' + containerId).empty();
        }
        this.paella = new Paella(containerId, {
            configUrl: configUrl,
            getManifestUrl: noop,
            getManifestFileUrl: noop,
            loadVideoManifest: loadVideoManifestFunction,
            customPluginContext: [
                require.context('./plugins', true, /\.js/),
                getBasicPluginContext(),
                getSlidePluginContext(),
                getZoomPluginContext(),
                getUserTrackingPluginsContext()
            ]
        });
        if (themeUrl != '') {
            await this.paella.skin.loadSkin(themeUrl);
        }
        this.paella.loadManifest()
        .then(() => {
            console.log("Initialization done");
        })
        .catch(e => console.error(e));

        this.paella.bindEvent(
            Events.PLAYER_LOADED,
            () => {
                if (!withBuffer) {
                    this.paella.playbackBar.progressIndicator.hideProgressTimer();
                    this.paella.playbackBar.progressIndicator.hideProgressContainer();
                    this.paella.playbackBar.progressIndicator.hideTimeLine();
                }
            },
            false
        );
    }
}
