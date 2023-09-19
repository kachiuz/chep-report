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

const renderChart1 = (colorArrayForChart1, stripLinesArray, dataPointsArray, startAndEndDate = " ") => {
	
	CanvasJS.addColorSet("customColorSet1", colorArrayForChart1);
	 
	 let chart = new CanvasJS.Chart("chart1InnerContainer", {
	  theme: "light1",
	  animationEnabled: true,
	  exportEnabled: true,
	  colorSet: "customColorSet1",
	  title: {
		//text: "Monthly pallet transfers by supplier "+startAndEndDate,
		fontSize: 20,
	  },
	  axisX: {
		titleFontSize: 20,
		labelFontSize: 14,
		tickPlacement: "inside",
		stripLines: stripLinesArray,
		//removes all labels from axes, later on they are added but as stripLines and with colors.
		labelFormatter: function(e) { 	return "";} 
	  },
	  axisY2: {
		title: "Pallet Quantity",
		titleFontSize: 20,
		labelFontSize: 14,
		includeZero: true,
	  },
	  data: [{
		type: "bar",
		indexLabelFontSize: 14,
		axisYType: "secondary",
		indexLabel: "{y}",
		dataPoints: dataPointsArray
	  }]
	});
	chart.render();
	chart.title.set("text", "Monthly pallet transfers by supplier "+startAndEndDate);
}


//in this array I will store individual colors for chart 1
//                  green       yellow      red        blue      orange    dark grey  yellow/green  brown   purple   dark blue
const colorArray = ["#408000", "#b3b300", "#cc3300", "#0059b3", "#cc7a00", "#5c5c8a", "#739900", "#997300", "#990073", "#2d5986"];

const organizeDataForChart1 = (chartData, distinctSuppliersNamesArray, distinctMonths, startAndEndDate) => {
	//count object keys to determine the length of the object
	let supplierCount = Object.keys(distinctSuppliersNamesArray).length;
	let monthCount = Object.keys(distinctMonths).length;
	
	//else a no data for chart will be displayed.
	if (supplierCount > 0) {
		
		let dataPointsArray = [];
		//a variable to store suppliers name+month value.
		let label = "";
		let y;
		
		//these array's will be filled inside the loops, and later passed to renderChart1 function
		let colorArrayForChart1 = [];
		 //draw the chart with data
		let colorIndex = 0;
		
		let stripLinesArray = [];
		//start/end values for strip line
		let startValue =-0.5; //as half of bar chart is on negative side
		let endValue =  monthCount - 0.5; //normally it would be equal to month count, but since 0.5 is in negative chart side, have to deduct 0.5             
		//color for strip line
		let color ="#e6e6e6";
		//if supplier count at least 1, a chart will be drawn
		
		
		for (let i =0; i< supplierCount; i++ ){
			 
			 //compose the array for dataPoints that will be used to draw the chart
			 for (let x=0; x< monthCount; x++){
				//I should have added a one more dimension to the multidimensional array at back end where the supplier array has 
				//it's indexes as number for each supplier. this way I could loop easier at front end.
				//now I have to rely on distinctSuppliersNamesArray[i] and it only works because the results in this array
				//are ordered in ascending order as are the suppliers names in resultArray. But it just dosn't make me feel comfortable 
				//as it doesn't look very reliable that I have to compose this result from two diffrent objects instead of one
				//as it leaves a possibility that things can go sideways.
				//but for the time being will leave it as i is.
				
				label = distinctSuppliersNamesArray[i]+" "+distinctMonths[x];
				//let label = distinctSuppliersNamesArray[i];
				y = chartData[distinctSuppliersNamesArray[i]][distinctMonths[x]];
				dataPointsArray.push({label, y});
				//fill array color
				colorArrayForChart1.push(colorArray[colorIndex]);
				
				
				///COLOR SUPPLIERS NAME WITH MONTH VALUE
				stripLinesArray.push({value: (monthCount*i+x), label: label, labelPlacement: "outside", labelBackgroundColor: "white", color: "transparent", labelFontColor: colorArray[colorIndex]});
				
			 }
			colorIndex++;
			 if(colorIndex === 10) {
				 //if all 10 colors in the color array has been used, reset the counter, and start filling the colorArrayForChart1 with
				 //the same colors again.
				 colorIndex = 0;
			 }
			 //adding striplines to seperate create a grid in the chart 
			 if(i==0) {
				//add default values on first iteration 
				stripLinesArray.push({startValue, endValue, color});
			 } else if (i % 2 == 0) {
				startValue+=(monthCount*2);
				endValue+=(monthCount*2);
				stripLinesArray.push({startValue, endValue, color});
			 }
		 }
		 
		 
			 
		//as this is a dynamic chart and it's height fluctuates depending of the amount of data retrieved
		//	 must calculate the height for chart1InnerContainer for the chart to look readable.
		//add 20px for each bar chart
		let chart1InnerContainerHeight = monthCount*supplierCount*20;
		
		//original height of inner container is 980px, so only change the style of this container if the chart1InnerContainerHeight value is higher the 980
		if (chart1InnerContainerHeight> 980) {	
			chart1InnerContainerHeight +="px";
			document.getElementById("chart1InnerContainer").style.height = chart1InnerContainerHeight;
		}

	//call the function to render the chart
	renderChart1(colorArrayForChart1, stripLinesArray, dataPointsArray, startAndEndDate); 
		 
	 } else {
		 //draw empty chart
		renderChart1(colorArray, [{value: 0, label: "Suppliers", labelPlacement: "outside", labelBackgroundColor: "white", color: "transparent", labelFontColor: "black"}],[{label:"supplier", y:0}]);
		 
	 }
}
const renderChart2 = (palletsIN, palletsOUT, startAndEndDate = " ") => {
	CanvasJS.addColorSet("greenRed", ["green", "red"]);
	let chart = new CanvasJS.Chart("chart2Container", {
		animationEnabled: true,
		exportEnabled: true,
		colorSet: "greenRed",
		theme: "light1",
		title:{
			fontSize: 20,
		},
		axisY: {
			title: "Pallet Quantity",
			labelFontSize: 14,
			titleFontSize: 20,
			includeZero: true,
		},
		data: [{        
			type: "column",  
			indexLabelFontSize: 14,
			indexLabel: "{y}",
			dataPoints: [      
				{ y: palletsIN, label: "Pallets IN" },
				{ y: palletsOUT,  label: "Pallets OUT" }
			]
		}]
	});
	chart.render();
	chart.title.set("text", "Pallets In and OUT "+startAndEndDate);
 }
const renderChart3 = (transferTypseSumsArray, startAndEndDate = " ") => {
	console.log(transferTypseSumsArray);
	CanvasJS.addColorSet("multipleGreenRed", ["green", "red", "green","red", "red", "green","red", "gree"]);
	let chart = new CanvasJS.Chart("chart3Container", {
		animationEnabled: true,
		exportEnabled: true,
		colorSet: "multipleGreenRed",
		theme: "light1",
		title:{
			fontSize: 20,
		},
		axisY: {
			title: "Pallet Quantity",
			labelFontSize: 14,
			titleFontSize: 20,
			includeZero: true,
		},
		data: [{        
			type: "column",  
			indexLabelFontSize: 14,
			indexLabel: "{y}",
			dataPoints: [      
				{ y: transferTypseSumsArray["Admin IN"], label: "Admin IN" },
				{ y: transferTypseSumsArray["Admin OUT"], label: "Admin OUT"},
				{ y: transferTypseSumsArray["Correction IN"], label: "Correction IN" },
				{ y: transferTypseSumsArray["Returns"], label: "Returns" },
				{ y: transferTypseSumsArray["Reversed Transfer IN"], label: "Reversed Transfer IN" },
				{ y: transferTypseSumsArray["Transfer IN"], label: "Transfer IN" },
				{ y: transferTypseSumsArray["Transfer OUT"], label: "Transfer OUT" },
				{ y: transferTypseSumsArray["Unknown"], label: "Unknown" },
			]
		}]
	});
	chart.render();
	chart.title.set("text", "Transfers by type "+startAndEndDate);
 }	
	 
const generateChart4 = () => {
	 
	 
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
					//a variable to store start and end date
					let startAndEndDate = response.startDate+" - "+response.endDate;
					organizeDataForChart1(response.resultArray, response.distinctSuppliersNamesArray, response.distinctMonths, startAndEndDate);
					renderChart2(response.palletsIN, response.palletsOUT, startAndEndDate);
					renderChart3(response.transferTypseSumsArray, startAndEndDate);
					generateChart4();
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
	
	//draw empty chart 1
	renderChart1(colorArray, [{value: 0, label: "Suppliers", labelPlacement: "outside", labelBackgroundColor: "white", color: "transparent", labelFontColor: "black"}],[{label:"supplier", y:0}]);
    renderChart2(0, 0);
	renderChart3([0,0,0,0,0,0,0,0]);
}
document.addEventListener("DOMContentLoaded",start,false);