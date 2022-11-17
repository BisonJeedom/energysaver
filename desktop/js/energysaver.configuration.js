modetrigger();

function modetrigger() {
  	for (var i = 1; i <= 3; i++) {  	
    	//var mode = $( "#modetrigger_" + i).is(':checked');
      	//var id = $("#checked_input_" + i).attr('data-id');
      	//console.log("mode: " + mode);
      	if ($( "#modetrigger_" + i).is(':checked')) {
      		$('#select_h' + i + '_stop').attr('disabled', 'disabled');
          	$('#select_m' + i + '_stop').attr('disabled', 'disabled');
            $('#select_h' + i + '_start').attr('disabled', 'disabled');
          	$('#select_m' + i + '_start').attr('disabled', 'disabled');
        } else {
            $('#select_h' + i + '_stop').attr('disabled', false);
          	$('#select_m' + i + '_stop').attr('disabled', false);
            $('#select_h' + i + '_start').attr('disabled', false);
          	$('#select_m' + i + '_start').attr('disabled', false); 
        }
    }
}