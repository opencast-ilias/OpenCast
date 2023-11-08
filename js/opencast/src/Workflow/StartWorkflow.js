/**
 * StartWorkflow
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class StartWorkflow {
    /**
     * @type {jQuery}
     */
    jquery;

    constructor(
        jquery,
    ){
        this.jquery = jquery;
    }

    init(submit_btn_id, form_id) {
        if (!submit_btn_id || !form_id) {
            return;
        }
        $(function() {
            $('#' + submit_btn_id).on('click', function(e) {
                e.preventDefault();
                let workflow_id = $('#workflow_id').val();
                if (!workflow_id || workflow_id == '') {
                    $('#wf-alert').removeClass('hidden');
                    $('#required').addClass('hidden');
                    $('#no-workflow-seleced').removeClass('hidden');
                    return;
                }
                let validated = true;
                let target_config_panel_id = '#config_panel_' + workflow_id;
                $(target_config_panel_id + ' .wf-inputs').each((index, element) => {
                    let required = $(element).attr('required');
                    let type = $(element).attr('type');
                    let value = $(element).val();
                    if (typeof required !== 'undefined' && required !== false && value.trim() == '') {
                        validated = false;
                    }
                    if (typeof type !== 'undefined') {
                        if (type == 'number' && !Number.isInteger(value)) {
                            validated = false;
                        }
                    }
                });
                if (validated) {
                    $('#' + form_id).trigger('submit');
                    let btn = $(this).parents('.modal-content').find('button.btn.btn-default.btn-primary');
                    if (btn.length) {
                        btn.prop('disabled', true);
                    }
                } else {
                    $('#wf-alert').removeClass('hidden');
                    $('#required').removeClass('hidden');
                    $('#no-workflow-seleced').addClass('hidden');
                }
                return false;
            });
            $('#workflow_id').on('change', function(e){
                $('#wf-alert').addClass('hidden');
                $('#required').addClass('hidden');
                $('#no-workflow-seleced').addClass('hidden');
                let workflow_id = this.value;
                let target_description_id = '#desc_' + workflow_id;
                let target_config_panel_id = '#config_panel_' + workflow_id;
                $('.workflows-description-section').addClass('hidden');
                $('.worflow-description-block').addClass('hidden');
                $('.workflows-configpanel-section').addClass('hidden');
                $('.workflows-configpanel-block').addClass('hidden');

                if ($(target_description_id).length) {
                    $('.workflows-description-section').removeClass('hidden');
                    $(target_description_id).removeClass('hidden');
                }

                $('.wf-inputs').each((index, element) => {
                    let input_id = $(element).attr('id');
                    if (input_id && $('#' + input_id + '_default').length) {
                        let default_value = $('#' + input_id + '_default').val();
                        $(element).val(default_value);
                    }
                });

                if ($(target_config_panel_id).length) {
                    $('.workflows-configpanel-section').removeClass('hidden');
                    $(target_config_panel_id).removeClass('hidden');
                }
            });
        });
    }
}
