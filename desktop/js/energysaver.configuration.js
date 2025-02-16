modetrigger();

function modetrigger() {
    // Désactivation des input d'heure si selection du mode déclencheur
    for (var i = 1; i <= 3; i++) {
        //var mode = $( "#modetrigger_" + i).is(':checked');
        //var id = $("#checked_input_" + i).attr('data-id');
        //console.log("mode: " + mode);
        if ($("#modetrigger_" + i).is(':checked')) {
            $('#select_h' + i + '_stop').attr('disabled', 'disabled');
            $('#select_m' + i + '_stop').attr('disabled', 'disabled');
            $('#select_h' + i + '_start').attr('disabled', 'disabled');
            $('#select_m' + i + '_start').attr('disabled', 'disabled');
            for (var k = 1; k <= 7; k++) {
                $('#checkbox_j' + k + '_' + i).attr('disabled', 'disabled');
            }
        } else {
            $('#select_h' + i + '_stop').attr('disabled', false);
            $('#select_m' + i + '_stop').attr('disabled', false);
            $('#select_h' + i + '_start').attr('disabled', false);
            $('#select_m' + i + '_start').attr('disabled', false);
            for (var k = 1; k <= 7; k++) {
                $('#checkbox_j' + k + '_' + i).attr('disabled', false);
            }
        }
    }
}

function energysaver_postSaveConfiguration() {
    // Après la sauvegarde de la configuration, appel ajax qui refresh le template via l'action SaveConfig
    $.ajax({
        type: "POST",
        url: "plugins/energysaver/core/ajax/energysaver.ajax.php",
        data: {
            action: "SaveConfig",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                return;
            }
            window.location.reload();
        }
    });
}