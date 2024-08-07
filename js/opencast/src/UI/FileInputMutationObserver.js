/**
 * FileInputMutationObserver
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class FileInputMutationObserver {

    config;
    targetNode;
    observer;
    childlist_callbacks;
    attribute_callbacks;
    constructor() {
        this.config = { attributes: true, childList: true, subtree: true };
        this.targetNode = null;
        this.observer = null;
        this.childlist_callbacks = [];
        this.attribute_callbacks = [];
    }

    init(file_input_id, childlist_callbacks = [], attribute_callbacks = []) {
        this.targetNode = document.getElementById(file_input_id);
        if (childlist_callbacks && childlist_callbacks.length) {
            this.childlist_callbacks = childlist_callbacks;
        }
        if (attribute_callbacks && attribute_callbacks.length) {
            this.attribute_callbacks = attribute_callbacks;
        }
        this.observerBinder = this.setObserver.bind(this);
        let this_class = this;
        $(function() {
            if (this_class.targetNode) {
                this_class.targetNode.addEventListener('click', this_class.observerBinder, true);
                this_class.targetNode.addEventListener('drop', this_class.observerBinder, true);
            }
        });
    }

    setObserver() {
        const callback = (mutationList, observer) => {
            for (const mutation of mutationList) {
                if (mutation.type === "childList") {
                    // Add as many function here.
                    if (this.childlist_callbacks && this.childlist_callbacks.length) {
                        for (const childlist_callback of this.childlist_callbacks) {
                            childlist_callback(this);
                        }
                    }
                } else if (mutation.type === "attributes") {
                    // Add more functions here if needed...
                    if (this.childlist_callbacks && this.childlist_callbacks.length) {
                        for (const attribute_callback of this.attribute_callbacks) {
                            attribute_callback(this);
                        }
                    }
                }
            }
            this.disconnectObserver();
        };
        this.observer = new MutationObserver(callback);
        this.observer.observe(this.targetNode, this.config);
    }

    disconnectObserver() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }
}
