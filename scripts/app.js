var ips_display  = $(".working_area.imported_ips textarea");
var sorted_ips_display  = $(".working_area.sorted_ips textarea");
var php_code_display = $(".working_area.phpcode_ips textarea");

var ips = [];

var onImported = function(contents){	
	ips_display.val(contents);
};

var onSorted = function(resp)
{
	if (resp["data"] !== undefined && resp["data"])
	{
		ips = resp["data"];
		var text = "";
		for (var i = 0; i < resp['data'].length; i++)
		{
			var range = resp['data'][i];
			text += range[0] + "-" + range[1] + "\n";
		}
		sorted_ips_display.val(text);
		sorted_ips_display.prop("disabled", false);
	}
	
};

$(document).ready(function(){
	
	var file_input = $("#importing_file");
	var pattern_modal = $("#php_pattern_modal");
	
	$("#to_php").click(function(){
		if (!ips || !ips.length)
		{
			alert("Empty content!");
			return false;
		}
		var coded_ips = prepare_to_php(ips);
		var pattern = pattern_modal.find("textarea").val();
		var code = "<?php " + coded_ips + " " + pattern;
		php_code_display.val(code);
		php_code_display.prop("disabled", false);
	});
	
	$("#set_php_pattern").click(function(){
		pattern_modal.modal("show");
	});
	
	$("#import_ips").click(function(){
		file_input.click();		
	});
	
	file_input.change(function(){
		if (file_input[0] !== undefined && file_input[0].files[0] !== undefined)
		{
			readSingleFile(file_input[0].files[0], onImported);			
		}		
	});
	
	$("#start").click(function() {
		var content = ips_display.val();
		if (!content.length)
		{
			alert("Empty content");
			return false;
		}
		$.ajax({
			type: "POST",
			url: "api.php",
			data: {action:"to_sort", content:content},
			success: onSorted
		});
	});
	
});

function prepare_to_php(ips)
{
	var code = "$names=array(";
	for (var i = 0; i < ips.length; i++)
	{
		var range = ips[i];
		code += "array('"+range[0]+"', '"+range[1]+"'),";
	}
	code += ");"
	return code;
}

function readSingleFile(file, callback) {  
  if (!file) {
    return;
  }
  if (file.size > 1024 * 1024)
  {
	alert("Max size of the file was exceeded! 1Mb");
	return false;
  }
  if (file.type != "text/plain")
  {
	alert("Supported type: .txt");
	return false;  
  }
  var reader = new FileReader();
  reader.onload = function(e) {
    var contents = e.target.result;
    callback(contents);
  };
  reader.readAsText(file);
}
