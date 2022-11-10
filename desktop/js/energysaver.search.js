function updateManagedEquipements(nb) {
    if(nb > 0){
        var equipements = [];
        for (var i = 1; i <= nb; i++) {
            if ($("#checked_input_" + i).is(':checked')) {
                var id = $("#checked_input_" + i).attr('data-id');
                equipements.push([{id: id}]);
            }
        }

        $.ajax({
            type: "POST",
            url: "plugins/energysaver/core/ajax/energysaver.ajax.php",
            data: {
                action: "updateManagedEquipements",
                data: equipements,
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                window.location.reload();
            }
        });
    }
}