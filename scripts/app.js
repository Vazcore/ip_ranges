var onImported = function(contents){
	var ips_display  = $(".working_area.imported_ips textarea");
	ips_display.val(contents);
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
