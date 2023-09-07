//I'll use arrays to store the data from chosen columns
let transactionTypeAray = []; //Transaction Type
let otherPartyArray = [] //Other Party ---> suppliers name
let quantityArray = []; //Quantity
let shipmentDateArray = []; //Shipment Date

var ExcelToJSON = function() {

      this.parseExcel = function(file) {
        var reader = new FileReader();

        reader.onload = function(e) {
          var data = e.target.result;
          var workbook = XLSX.read(data, {
            type: 'binary'
          });
          workbook.SheetNames.forEach(function(sheetName) {
            // Here is your object
            var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
            var json_object = JSON.stringify(XL_row_object);

		let parsedObject = JSON.parse(json_object);
		let size = Object.keys(parsedObject).length;

		
		//check if the names in excel are proper.
		//if it is named properly do nothing, else thorw an error 
		//Transaction Type
		if (parsedObject[0]["Transaction Type"]) {
			//do nothing
		} else {alert("Error! In the excel file there has to be a column named 'Transaction Type'"); return false}
		//Other Party
		if (parsedObject[0]["Other Party"]) {
			//do nothing
		} else {alert("Error! In the excel file there has to be a column named 'Other Party'"); return false}
		//Quantity
		if (parsedObject[0]["Quantity"]) {
			//do nothing
		} else {alert("Error! In the excel file there has to be a column named 'Quantity'"); return false}

		//Shipment Date
		if (parsedObject[0]["Shipment Date"]) {
			//do nothing
		} else {alert("Error! In the excel file there has to be a column named 'Shipment Date'"); return false}

		//In case the page wasn't reloaded and several files are being uploaded one after another
		//I must make sure each time the previuos values that were stored in these arrays are deleted.
		transactionTypeAray = []; //Transaction Type
		otherPartyArray = [] //Other Party ---> suppliers name
		quantityArray = []; //Quantity
		shipmentDateArray = []; //Shipment Date
		
		
		for (let i = 0; i < size; i++) {
			
			//since in back end I use explode function and a comma as a seperator, I must make sure there are no comas left in any of the strings
			//hence will replace all comas with empty space;
			
			//remove commas from all rows
			//this is only neccesasry for suppliers name, but just in case I remove it from all values.
			parsedObject[i]["Transaction Type"] = parsedObject[i]["Transaction Type"].replace(/,/g, "");
			parsedObject[i]["Other Party"] = parsedObject[i]["Other Party"].replace(/,/g, "");
			parsedObject[i]["Quantity"] = parsedObject[i]["Quantity"].replace(/,/g, "");
			parsedObject[i]["Shipment Date"] = parsedObject[i]["Shipment Date"].replace(/,/g, "");
			
			//remove '&' from strings as in php it stops the explode function.
			//this is only neccesasry for suppliers name, but just in case I remove it from all values.
			parsedObject[i]["Other Party"] = parsedObject[i]["Other Party"].replace(/&/g, "");
			parsedObject[i]["Transaction Type"] = parsedObject[i]["Transaction Type"].replace(/&/g, "");
			parsedObject[i]["Quantity"] = parsedObject[i]["Quantity"].replace(/&/g, "");
			parsedObject[i]["Shipment Date"] = parsedObject[i]["Shipment Date"].replace(/&/g, "");
						
			//add values to arrays
			transactionTypeAray.push(parsedObject[i]["Transaction Type"]);
			otherPartyArray.push(parsedObject[i]["Other Party"]);
			quantityArray.push(parsedObject[i]["Quantity"]);
			shipmentDateArray.push(parsedObject[i]["Shipment Date"]);
		}	
		//fetch all arrays and add them to the string that will be sent over to back end.
			let str = 'transactionTypeAray='+transactionTypeAray+'&'+'otherPartyArray='+otherPartyArray+'&';
			str+='quantityArray='+quantityArray+'&'+'shipmentDateArray='+shipmentDateArray+'&';
			sendValuesToServer(str);
            //jQuery( '#xlx_json' ).val( json_object );
          })
        };

        reader.onerror = function(ex) {
          console.log(ex);
        };

        reader.readAsBinaryString(file);
      };
  };

  function handleFileSelect(evt) {
    
    var files = evt.target.files; // FileList object
    var xl2json = new ExcelToJSON();
    xl2json.parseExcel(files[0]);
  }
  
  
//send values to back end.  
 const sendValuesToServer = str => {
	let request;
	if (XMLHttpRequest)
		{
			request = new XMLHttpRequest();
		}
			else if (ActiveXObject)
		{
			request = new ActiveXObject("Microsoft.XMLHTTP");
		}
	else {return false;}
	
	let responseDiv = document.getElementById("responseDiv");
	let url = "updateDatabase.php";
	request.open("POST", url, true);
	request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	request.onreadystatechange = function(){
		if(request.readyState ==4 && request.status ==200){
				
				let  response = JSON.parse(this.responseText);
				
				if (response.errors.length > 0){
					//if there are errors alert about them
					
					alert(response.errors);
					return false;
					
				} else {
					setTimeout(function(){responseDiv.innerHTML=" ";},1500);
				}
				
		} else if(request.readyState==4 && request.status==0 ) {
			notConnectedError();
		} else {}
	}
	request.send(str);
	responseDiv.innerHTML = "Generating report...";
}


 const start = ()=> {
	 
	 document.getElementById('uploadButton').addEventListener('change', handleFileSelect, false);
	 
	//let updateButton = document.getElementById("updateButton");
	//updateButton.onclick = getUpdateInput;

}
document.addEventListener("DOMContentLoaded",start,false);