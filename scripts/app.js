var ips_display  = $(".working_area.imported_ips textarea");
var sorted_ips_display  = $(".working_area.sorted_ips textarea");

var onImported = function(contents){	
	ips_display.val(contents);
};

var onSorted = function(resp)
{
	sorted_ips_display.val(resp);
};

$(document).ready(function(){
	
	var file_input = $("#importing_file");
	
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
