/**
 * Tiles
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
export default class Tiles {

    constructor() {

    }

    init() {
        this.registerToggle();
    }

    registerToggle() {
        $('#tiles_per_page').on('change', function () {
            $('#xoct_limit_selector').submit();
        });
    }

}
