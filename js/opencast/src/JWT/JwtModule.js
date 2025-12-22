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

    constructor() {
        this.player_url = null;
        this.iframe_origin = null;
        this.refresh_token_url = null;
        this.event_id = null;
        this.iframe_id = 'opencastPaellaJwtPlayer';
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
                console.log('SENDING PACK NEW JWT...');
                // TESTING PURPOSE!
                console.log('ev.data', ev.data);
                if (ev.data.jwt) {
                    console.log('jwt', ev.data.jwt);
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
        // srcUrlObj.searchParams.set('id', this.event_id);
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
}
