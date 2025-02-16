function updateManagedEquipements(nb) {
    if (nb > 0) {
        var equipements = [];
        var equipementsId = [];
        var equipementsValue = [];
        for (var i = 1; i <= nb; i++) {
            var value = $("#checked_input_" + i).val();
            if (value != 0) {
                var id = $("#checked_input_" + i).attr('data-id');
                console.log("id : " + id);
                equipements.push([{ id: id }, { schedule: value }]);
            }

            /*
        if ($("#checked_input_" + i).is(':checked')) {
            var id = $("#checked_input_" + i).attr('data-id');
            //equipements.push([{id: id}]);
        }
        */
        }

        console.table(equipements);

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
                    $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                    return;
                }
                window.location.reload();
            }
        });
    }
}