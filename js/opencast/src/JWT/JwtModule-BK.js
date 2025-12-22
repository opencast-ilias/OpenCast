/**
 * JwtModule
 *
 * JwtModule class to handle JWT token injection into OpenCast URLs and token refreshing.
 *
* @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class JwtModule {
    base_url;
    refresh_token_url;
    refresh_interval_ms;
    selectors;

    constructor() {
        this.base_url = null;
        this.refresh_token_url = null;
        this.refresh_interval_ms = 15000;
        this.selectors = ['img[src]', 'video[src]', 'source[src]', 'a[href]'];
    }

    init(base_url, refresh_token_url, refresh_interval_ms = 15000, selectors = null) {
        this.base_url = base_url;
        this.refresh_token_url = refresh_token_url;
        this.refresh_interval_ms = refresh_interval_ms;
        if (selectors) {
            this.selectors = selectors;
        }
        if (!this.base_url) {
            console.warn('JwtModule: base_url is not set, skipping initialization.');
            return;
        }
        console.log('HERE...');
        // setInterval(this.fetchAndUpdateStaticUrls.bind(this), this.refresh_interval_ms);
// const iframeBase = "http://localhost:7070/paella7/ui/watch.html";
// const eventId = "19be2fb4-0241-4947-a6e0-28873e857a7d";


// document.addEventListener("DOMContentLoaded", () => {
// const iframe = document.getElementById("iframe");
// fetchJwt(eventId).then(jwt => {
// iframe.src = iframeBase + '?' + new URLSearchParams({
// id: eventId,
// jwt,
// jwtRefresh: 'true'
// });
// });

// window.addEventListener("message", ev => {
// // TODO: make sure it's from the expected iframe!
// if (typeof ev.data === "object" && ev.data?.type === "oc-event-jwt-request" && typeof ev.data?.event === "string") {
// fetchJwt(ev.data.event).then(jwt => {
// ev.source.postMessage({
//     type: "oc-event-jwt",
//     event: ev.data.event,
//     jwt,
// }, '*');
// });
// }
// });
// })

// // Just for testing, fetch JWT from a running Tobira lol
// const fetchJwt = async (eventId) => {
// const response = await fetch("/graphql", {
// method: "POST",
// headers: {
//     "Content-Type": "application/json",
// },
// body: JSON.stringify({
//     query: "query($events: [String!]!) { eventReadJwts(events:$events) { event jwt } }",
//     variables: {
//         events: [eventId],
//     },
// }),
// });
// if (response.status !== 200) {
// throw new Error("unexpected non-200 response from API");
// }
// const data = await response.json();

// // Read & convert data, making sure it has the expected format
// const arr = data?.data?.eventReadJwts;
// if (!arr || !Array.isArray(arr)) {
// throw new Error("unexpected API response data");
// }

// return arr[0].jwt;
// }

    }

    fetchAndUpdateStaticUrls() {
        document.querySelectorAll(this.selectors.join(',')).forEach(el => {
            const attr = el.tagName === 'A' ? 'href' : 'src';
            const url = el.getAttribute(attr);
            if (!url || !url.startsWith(this.base_url)) return;

            console.log('el.tagName', el.tagName);
            const token = this.extractJwtFromUrl(url);
            if (token) {
                $.ajax({
                    url: this.refresh_token_url,
                    type: "POST",
                    data: {
                        "token": token
                    }
                }).done(function (data, textStatus, jqXHR) {
                    let dataObj = JSON.parse(data);
                    const urlObj = new URL(url);
                    urlObj.searchParams.set('jwt', dataObj.newToken);
                    let newUrl = urlObj.toString();
                    el.setAttribute(attr, newUrl);
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.error('JwtModule: Failed to refresh token:', jqXHR.statusText);
                });
            }
        });
    }

    validateAndRefreshToken(url) {
        // Validate the JWT token in the URL
        const token = this.extractJwtFromUrl(url);
        if (!token) {
            console.warn('JwtModule: No JWT found in URL:', url);
            return;
        }

        // Refresh the token if it's expired or about to expire
        this.refreshToken(token).then(newToken => {
            console.log('JwtModule: Refreshed token:', newToken);
            if (newToken) {
                this.replaceJwtInUrl(url, newToken);
            }
        });
    }

    extractJwtFromUrl(url) {
        const urlObj = new URL(url);
        return urlObj.searchParams.get('jwt');
    }

    replaceJwtInUrl(url, newToken) {
        const urlObj = new URL(url);
        urlObj.searchParams.set('jwt', newToken);
        return urlObj.toString();
    }

    async refreshToken(oldToken) {
        // console.log('JwtModule: Refreshing token:', oldToken);
        // console.log('JwtModule: this.refresh_token_url:', this.refresh_token_url);
        try {
            $.ajax({
                url: this.refresh_token_url,
                type: "POST",
                data: {
                    "token": oldToken
                }
            }).done(function (data, textStatus, jqXHR) {
                let dataObj = JSON.parse(data);
                return dataObj.newToken;
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.error('JwtModule: Failed to refresh token:', jqXHR.statusText);
                return null;
            });
            // const response = await fetch(this.refresh_token_url, {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //     },
            //     body: JSON.stringify({ token: oldToken }),
            // });
            // if (!response.ok) {
            //     console.error('JwtModule: Failed to refresh token:', response.statusText);
            //     return null;
            // }
            // const data = await response.json();
            // console.log('data', data);
            // return '';
        } catch (error) {
            console.error('JwtModule: Error refreshing token:', error);
            return null;
        }
    }
}
