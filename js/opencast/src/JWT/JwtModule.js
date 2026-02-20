/**
 * JwtModule
 *
 * JwtModule to communicate with opencast regarding playing video in a iframe with token refresh.
 *
* @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class JwtModule {
    player_url;
    refresh_token_url;
    event_id;
    iframe_origin;
    iframe_id;
    overlay_live_waiting_id;
    overlay_live_over_id;
    overlay_live_interrupted_id;
    is_live_stream;
    start_time_utc;
    start_time;
    end_time_utc;
    end_time;
    hls_source_urls;
    hls_check_script;
    hls_source_format;
    hls_status_check_interval;
    livestream_state_interval;
    is_livestream_running;
    livestream_has_valid_src;

    constructor() {
        this.player_url = null;
        this.iframe_origin = null;
        this.refresh_token_url = null;
        this.event_id = null;
        this.iframe_id = 'opencastPaellaJwtPlayer';
        this.overlay_live_waiting_id = 'overlay_live_waiting';
        this.overlay_live_over_id = 'overlay_live_over';
        this.overlay_live_interrupted_id = 'overlay_live_interrupted';
        this.is_live_stream = false;
        this.start_time_utc = null;
        this.end_time_utc = null;
        this.hls_source_urls = [];
        this.hls_check_script = null;
        this.hls_source_format = null;
        this.hls_status_check_interval = null;
        this.livestream_state_interval = null;
        this.is_livestream_running = false;
        this.livestream_has_valid_src = true;
    }

    init(config) {
        this.player_url = config.player_url;
        this.refresh_token_url = config.refresh_token_url;
        this.event_id = config.event_id;
        if (!this.player_url || !this.event_id || !this.refresh_token_url) {
            console.warn('JwtModule: unable to initialize the JWT module due to insufficient arguments!');
            return;
        }
        this.iframe_origin = this.extractBaseUrl();
        this.registerListeners();

        // Do some live stream specific initialization if this is a live stream event.
        this.is_live_stream = config.is_live_stream || false;
        this.hls_source_urls = config.hls_source_urls || [];
        this.hls_check_script = config.hls_check_script || null;
        this.hls_source_format = config.hls_source_format || null;
        this.hls_status_check_interval = null;
        this.livestream_state_interval = null;
        this.livestream_has_valid_src = true;
        this.start_time_utc = config.start_time_utc || null;
        this.end_time_utc = config.end_time_utc || null;
        if (this.is_live_stream) {
            this.initLivestreamHandlers();
        } else {
            this.showIframe();
        }
    }

    registerListeners() {
        // First on load, we generate the iframe url.
        document.addEventListener("DOMContentLoaded", () => {
            const iframe = document.getElementById(this.iframe_id);
            this.fetchTokenForEvent(this.event_id).then(jwt => {
                if (!jwt) {
                    return;
                }
                this.generateIframeUrl(jwt);

                // Testing purposes!
                setTimeout(() => {
                    iframe.contentWindow.postMessage(
                        {
                            type: "oc-event-jwt",
                            event: this.event_id,
                            jwt: jwt,
                        },
                        this.iframe_origin
                    );
                }, 500);
            });
        });

        // We also register the message Listener to listen to the refresh token request from the iframe.
        window.addEventListener("message", ev => {
            if (this.iframe_origin !== ev.origin) {
                console.warn('Invalid JWT iframe origin, skipping...', ev.origin);
                return;
            }

            // Make sure the message contains the proper data.
            if (typeof ev.data === "object"
                && (ev.data.type && ev.data.type === "oc-event-jwt-request")
                && (ev.data.event && typeof ev.data.event === 'string')) {

                // A tiny check to make sure that the incoming data is for this event!
                if (ev.data.event !== this.event_id) {
                    // TODO: Would this console warn clutter the console logs!?
                    console.warn('Event ID not matched, skipping...', this.event_id, ev.data.event);
                    return;
                }
                this.fetchTokenForEvent(ev.data.event).then(jwt => {
                    if (!jwt) {
                        console.error('JwtModule: failed to send new JWT back to opencast!');
                        return;
                    }
                    ev.source.postMessage(
                        {
                            type: "oc-event-jwt",
                            event: ev.data.event,
                            jwt: jwt,
                        },
                        this.iframe_origin
                    );
                });
            }
        });
    }

    extractBaseUrl() {
        let urlObj = new URL(this.player_url);
        return `${urlObj.protocol}//${urlObj.hostname}` + (urlObj.port ? ':' + urlObj.port : '');
    }

    generateIframeUrl(jwt) {
        if (!jwt || typeof jwt !== 'string') {
            console.error('JwtModule: unable to generate iframe source url due to invalid JWT!');
            return;
        }
        const srcUrlObj = new URL(this.player_url);
        srcUrlObj.searchParams.set('jwt', jwt);
        srcUrlObj.searchParams.set('jwtRefresh', 'true');
        const iframe = document.getElementById(this.iframe_id);
        iframe.src = srcUrlObj.toString();
    }

    async fetchTokenForEvent() {
        try {
            let response = await fetch(this.refresh_token_url);
            if (!response.ok) {
                console.error('JwtModule: HTTP error, status:', response.status);
                return null;
            }
            const data = await response.json();

            if (data.status !== 'OK' && data.message) {
                console.error('JwtModule: response error:', data.message);
                return null;
            }

            if (data.newToken) {
                return data.newToken;
            }

            return null;
        } catch (error) {
            console.error('JwtModule: Error fetching JWT for the event:', this.event_id, error);
            return null;
        }
    }

    hideOverlays() {
        this.displayElementsHandlers(this.overlay_live_over_id, false);
        this.displayElementsHandlers(this.overlay_live_waiting_id, false);
        this.displayElementsHandlers(this.overlay_live_interrupted_id, false);
    }

    showOverlay(type) {
        if (!['waiting', 'over', 'interrupted'].includes(type)) {
            return;
        }
        this.hideOverlays();
        let element_id = null;
        if (type === 'waiting') {
            element_id = this.overlay_live_waiting_id;
        } else if (type === 'over') {
            element_id = this.overlay_live_over_id;
        } else if (type === 'interrupted') {
            element_id = this.overlay_live_interrupted_id;
        }
        this.displayElementsHandlers(element_id, true);
    }

    showIframe() {
        this.displayElementsHandlers(this.iframe_id, true);
    }

    hideIframe() {
        this.displayElementsHandlers(this.iframe_id, false);
    }

    displayElementsHandlers(id, display) {
        const element = document.getElementById(id);
        if (!element) {
            return;
        }
        if (display && element.classList.contains('hidden')) {
            element.classList.remove('hidden');
        } else if (!display && !element.classList.contains('hidden')) {
            element.classList.add('hidden');
        }
    }

    initLivestreamHandlers() {
        if (!this.start_time_utc || !this.end_time_utc) {
            this.hideOverlays();
            this.showIframe();
            this.is_livestream_running = true;
            return;
        }

        this.start_time = new Date(this.start_time_utc);
        this.end_time = new Date(this.end_time_utc);

        this.updateLiveStreamState();
        this.livestream_state_interval = setInterval(() => {
            if (this.livestream_has_valid_src) {
                this.updateLiveStreamState();
            }
        }, 1000);

        this.hls_status_check_interval = setInterval(async () => {
            await this.checkHlsStatus();
        }, 5000);

    }

    updateLiveStreamState() {
        const now = new Date();

        if (now < this.start_time) {
            this.showOverlay('waiting');
            this.hideIframe();
            this.is_livestream_running = false;
        } else if (now > this.end_time) {
            this.hideIframe();
            this.showOverlay('over');
            this.is_livestream_running = false;
            if (this.livestream_state_interval) {
                clearInterval(this.livestream_state_interval);
                this.livestream_state_interval = null;
            }
            if (this.hls_status_check_interval) {
                clearInterval(this.hls_status_check_interval);
                this.hls_status_check_interval = null;
            }
        } else {
            this.hideOverlays();
            this.showIframe();
            this.is_livestream_running = true;
        }
    }

    async checkHlsStatus() {
        if (
            !this.hls_check_script ||
            !this.hls_source_urls.length ||
            !this.is_live_stream ||
            !this.is_livestream_running
        ) {
            return;
        }

        let hasValidSource = false;

        for (const url of this.hls_source_urls) {
            if ((await this.validateHlsUrl(url))) {
                hasValidSource = true;
                break;
            }
        }

        if (!hasValidSource) {
            this.showOverlay('interrupted');
            this.hideIframe();
            this.livestream_has_valid_src = false;
        } else {
            this.livestream_has_valid_src = true;
        }
    }

    async validateHlsUrl(url) {
        try {
            const path = this.generateHlsCheckScriptUrl(url);
            return (await $.get(path) === 'true');
        } catch (error) {
            console.error('JwtModule: Error checking HLS status:', error);
        }
        return false;
    }

    generateHlsCheckScriptUrl(url) {
        if (!url || typeof url !== 'string') {
            return null;
        }
        const scriptUrlObj = new URL(this.hls_check_script);
        scriptUrlObj.searchParams.set('url', url);
        scriptUrlObj.searchParams.set('livestream_type', this.hls_source_format);
        return scriptUrlObj.toString();
    }
}
