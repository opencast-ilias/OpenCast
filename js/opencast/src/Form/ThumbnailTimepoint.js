/**
 * ThumbnailTimepoint
 *
 * Keeps the thumbnail timepoint field (HH:MM:SS) inside the length of the video
 * that is about to be uploaded: the duration is read from the selected file in
 * the browser and an entered timepoint beyond it is clamped down.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */

const PLACEHOLDER = '00:00:00';

// Same format the server side constraint in EventFormBuilder accepts.
const TIMEPOINT_PATTERN = /^\d{1,3}:[0-5]\d:[0-5]\d$/;

// The video file input and this field are initialized by separate onLoad
// snippets and the order between them is not guaranteed, so wait for the
// dropzone instead of assuming it is already there.
const DROPZONE_POLL_INTERVAL = 200;
const DROPZONE_POLL_LIMIT = 25;

export default class ThumbnailTimepoint {
    field;
    input;
    byline;
    byline_text;
    max_hint;
    max_seconds;

    constructor() {
        this.field = null;
        this.input = null;
        this.byline = null;
        this.byline_text = '';
        this.max_hint = '';
        this.max_seconds = null;
    }

    /**
     * @param {string} field_id id of the fieldset wrapping the timepoint input
     * @param {{max_hint: string}} options max_hint carries one %s for the duration
     */
    init(field_id, options = {}) {
        this.field = document.getElementById(field_id);
        if (this.field === null) {
            return;
        }
        this.input = this.field.querySelector('input');
        if (this.input === null) {
            return;
        }
        this.byline = this.field.querySelector('.c-input__help-byline');
        this.byline_text = (this.byline !== null) ? this.byline.textContent : '';
        this.max_hint = options.max_hint || '';

        this.input.setAttribute('placeholder', PLACEHOLDER);
        this.input.addEventListener('change', () => this.clamp());
        this.input.addEventListener('blur', () => this.clamp());

        this.whenVideoDropzoneReady((dropzone) => {
            dropzone.on('addedfile', (file) => this.readDuration(file));
            dropzone.on('removedfile', () => this.setMax(null));
        });
    }

    /**
     * Hands the dropzone of the video file input to the callback, once it exists.
     *
     * The dropzone is used rather than the file input itself because dropzone.js
     * appends its hidden file input to document.body and drag and drop never
     * fires a change event on it.
     */
    whenVideoDropzoneReady(callback) {
        let attempts = 0;
        const look = () => {
            // The video file input tags itself with this attribute in its own
            // onLoad code, see EventFormBuilder::upload().
            const video_field = document.querySelector('[data-videoFileInput]');
            const element = (video_field !== null)
                ? video_field.querySelector('.ui-input-file-input-dropzone')
                : null;
            // dropzone.js puts the instance on the element it was built on.
            const dropzone = (element !== null && element.dropzone) ? element.dropzone : null;
            if (dropzone !== null) {
                callback(dropzone);
                return;
            }
            attempts++;
            if (attempts < DROPZONE_POLL_LIMIT) {
                setTimeout(look, DROPZONE_POLL_INTERVAL);
            }
            // Giving up is fine: without a duration the field just stays unbounded.
        };
        look();
    }

    readDuration(file) {
        const object_url = URL.createObjectURL(file);
        const probe = document.createElement('video');
        probe.preload = 'metadata';
        probe.onloadedmetadata = () => {
            const duration = probe.duration;
            URL.revokeObjectURL(object_url);
            this.setMax(
                (Number.isFinite(duration) && duration > 0) ? Math.floor(duration) : null
            );
        };
        probe.onerror = () => {
            // The browser cannot read this container (some mkv for instance).
            // Leave the field unbounded, this must never block an upload.
            URL.revokeObjectURL(object_url);
            this.setMax(null);
        };
        probe.src = object_url;
    }

    setMax(seconds) {
        this.max_seconds = seconds;

        if (seconds === null) {
            this.input.setAttribute('placeholder', PLACEHOLDER);
            if (this.byline !== null) {
                this.byline.textContent = this.byline_text;
            }
            return;
        }

        const formatted = toHms(seconds);
        this.input.setAttribute('placeholder', formatted);
        if (this.byline !== null) {
            this.byline.textContent = (this.byline_text + ' ' + this.max_hint.replace('%s', formatted)).trim();
        }
        this.clamp();
    }

    clamp() {
        if (this.max_seconds === null) {
            return;
        }
        const seconds = toSeconds(this.input.value);
        // An unparsable value is left alone, the server side constraint reports it.
        if (seconds === null || seconds <= this.max_seconds) {
            return;
        }
        this.input.value = toHms(this.max_seconds);
    }
}

function toSeconds(value) {
    if (!TIMEPOINT_PATTERN.test(value)) {
        return null;
    }
    const [hours, minutes, seconds] = value.split(':').map(Number);
    return (hours * 3600) + (minutes * 60) + seconds;
}

function toHms(seconds) {
    return [
        Math.floor(seconds / 3600),
        Math.floor((seconds % 3600) / 60),
        seconds % 60
    ].map((part) => String(part).padStart(2, '0')).join(':');
}
