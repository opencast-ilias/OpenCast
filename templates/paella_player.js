xoctPaellaPlayer = {
    
    data: [],
    
    config: {
        paella_config_file: '',
        paella_player_folder: '',
        is_live_stream: false,
        event_start: 0,
        event_end: 0,
        check_script_hls: ''
    },

    status: 'playing',

    init: function(data, config) {
        this.data = data;
        this.config = config;
        if (this.config.is_live_stream === true) {
            this.hasWorkingStream().then(function(stream_available) {
                if (stream_available === 'true') {
                    xoctPaellaPlayer.loadPlayer();
                    xoctPaellaPlayer.checkStreamStatus();
                } else {
                    xoctPaellaPlayer.triggerOverlays();
                    xoctPaellaPlayer.checkAndLoadLive();
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

    reloadPlayer: function() {
        paella.player.pause();
        delete(paella);
        xoctPaellaPlayer.reloadScript(xoctPaellaPlayer.config.paella_player_folder + '/javascript/paella_player.js');
        xoctPaellaPlayer.reloadScript(xoctPaellaPlayer.config.paella_player_folder + '/javascript/base.js');
        $('#playerContainer').empty();
        // the loading of the script takes some time, that's why we need an interval
        let i = setInterval(function() {
            if (typeof paella == 'object') {
                xoctPaellaPlayer.loadPlayer();
                paella.player.play();
                clearInterval(i);
            }
        }, 500);
    },

    reloadScript: function(src) {
        let scriptElement = document.createElement('script');
        scriptElement.type = 'text/javascript';
        scriptElement.src = src + '?' + (new Date).getTime();
        document.getElementsByTagName('head')[0].appendChild(scriptElement);
    },

    loadPlayer: function() {
        $('#overlay_live_waiting').hide();
        this.filterStreams().then(() => {
            paella.load('playerContainer', {
                data: xoctPaellaPlayer.data,
                configUrl: xoctPaellaPlayer.config.paella_config_file,
            });
        });
    },

    triggerOverlays: function() {
        let ts = Math.round(new Date().getTime() / 1000);
        if (ts < xoctPaellaPlayer.config.event_start) {
            xoctPaellaPlayer.showOverlay('waiting');
        } else if (ts > xoctPaellaPlayer.config.event_end) {
            xoctPaellaPlayer.showOverlay('over');
        } else {
            xoctPaellaPlayer.showOverlay('interrupted');
        }
    },

    showOverlay: function(status) {
        xoctPaellaPlayer.hideOverlays();
        $('#overlay_live_' + status).show();
    },

    hideOverlays: function() {
        $('#overlay_live_waiting').hide();
        $('#overlay_live_interrupted').hide();
        $('#overlay_live_over').hide();
        // avoids a bug with missing sound track
        // $('#playerContainer_videoContainer').click()
    },

    isStreamWorking: async function(url) {
        return await $.get(xoctPaellaPlayer.config.check_script_hls + "?url=" + url);
    },

    /**
     * check for working streams
     * @returns {Promise<boolean>}
     */
    hasWorkingStream: async function() {
        var working_stream_found = false;
        for (const stream of xoctPaellaPlayer.data.streams) {
            if (working_stream_found === false ) {
                working_stream_found = await xoctPaellaPlayer.isStreamWorking(stream.sources.hls[0].src);
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
        var i = setInterval(async function() {
            if (await xoctPaellaPlayer.hasWorkingStream() === 'true') {
                xoctPaellaPlayer.hideOverlays();
                xoctPaellaPlayer.loadPlayer();
                clearInterval(i);
                xoctPaellaPlayer.checkStreamStatus();
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
        let f = async function() {
            console.log('check stream status');
            var ts = Math.round(new Date().getTime() / 1000);
            if (await xoctPaellaPlayer.hasWorkingStream() !== 'true') {
                if (ts < xoctPaellaPlayer.config.event_start) {
                    xoctPaellaPlayer.status = 'waiting';
                } else if (ts >= xoctPaellaPlayer.config.event_end) {
                    xoctPaellaPlayer.status = 'over';
                } else {
                    xoctPaellaPlayer.status = 'interrupted';
                }
                xoctPaellaPlayer.hideOverlays();
                xoctPaellaPlayer.showOverlay(xoctPaellaPlayer.status);
                paella.player.pause();
                clearInterval(i);
                if (xoctPaellaPlayer.status === 'over') {
                    $('#playerContainer').empty();
                } else {
                    i = setInterval(f, 2000);
                }
            } else {
                if (xoctPaellaPlayer.status !== 'playing') {
                    xoctPaellaPlayer.reloadPlayer();
                }
                xoctPaellaPlayer.status = 'playing';
                xoctPaellaPlayer.hideOverlays();
                clearInterval(i);
                i = setInterval(f, 20000);
            }
        };
        i = setInterval(f, 20000)
    },

    filterStreams: async function() {
        if (this.config.is_live_stream) {
            for (const streamKey in xoctPaellaPlayer.data.streams) {
                if (xoctPaellaPlayer.data.streams.hasOwnProperty(streamKey) &&
                  (await xoctPaellaPlayer.isStreamWorking(xoctPaellaPlayer.data.streams[streamKey].sources.hls[0].src) !== 'true')) {
                    xoctPaellaPlayer.data.streams.splice(streamKey, 1);
                }
            }
        }
    }
}
