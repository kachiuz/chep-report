<?php
date_default_timezone_set("Europe/London");
/*error_reporting( E_ALL );
  ini_set( "display_errors", 1 ); */
$errors = array();
//only run scripts if the request method is post
if ( $_SERVER[ 'REQUEST_METHOD' ] != 'POST' ){
	$errors[] = 'Wrong Method!';
	$arrayForFrontEnd = array("errors"=>$errors);
	Die ($jsonFile = json_encode($arrayForFrontEnd));
}
$arrayForFrontEnd = array();

//report error function which gets called if the row inside the excel file are empty
function ReportErrorForEmptyExcelFile() {
	$arrayForFrontEnd = array(); //needs to be redeclared, as glogal variables are not accessible inside PHP function
	$errors[] = 'Error! Please check that rows in excel file are not empty.';
	$arrayForFrontEnd += array("errors"=>$errors);
	Die ($jsonFile = json_encode($arrayForFrontEnd));
}

//check if arrays have been submited;

//let's count how many of the preferable arrays have not been submitted.
//I do that in order to avoid 4 differenet errors being reported at the same time at front end, in case a file with different column
//names has been subimted.
$errorTrue = 0;
if(!empty($_POST['transactionTypeAray'])){
	$transactionTypeAray = explode(",", $_POST['transactionTypeAray']);
}else{
	$errorTrue++;
}
if(!empty($_POST['otherPartyArray'])){
	$otherPartyArray = explode(",", $_POST['otherPartyArray']);
}else{
	$errorTrue++;
}
if(!empty($_POST['quantityArray'])){
	$quantityArray = explode(",", $_POST['quantityArray']);
}else{
	$errorTrue++;
}
if(!empty($_POST['shipmentDateArray'])){
	$shipmentDateArray = explode(",", $_POST['shipmentDateArray']);
}else{
	$errorTrue++;
}

if($errorTrue>0){
	$errors[] = 'One or more column names in the excel file is not named properly! Please check the requirements for the file and name columns accordingly.';
	$arrayForFrontEnd += array("errors"=>$errors);
	Die ($jsonFile = json_encode($arrayForFrontEnd));
}

require('../shortageReport_connectDB.php');

//find the length of array;
$arrayLength = Count($transactionTypeAray );

//Insert data into database by using for loop.
for ($i = 0; $i <$arrayLength; $i++){

	//I want to extract year and month from date as I intend to group results by month
	$splitShipmentDateArray= explode("-", $shipmentDateArray[$i]);
	//compose proper date
	$yearMonthDate = $splitShipmentDateArray[0].''.$splitShipmentDateArray[1];

	//since suppliers name might contain characters that interfare with insert into query, need to make sure this is avoided.
	$otherPartyArray[$i] = htmlentities(mysqli_real_escape_string($shortageReportDB, $otherPartyArray[$i]));
	
	//just in case I will do the same safety check with other values as well
	$transactionTypeAray[$i] = htmlentities(mysqli_real_escape_string($shortageReportDB, $transactionTypeAray[$i]));
	$quantityArray[$i] = htmlentities(mysqli_real_escape_string($shortageReportDB, $quantityArray[$i]));
	$shipmentDateArray[$i] = htmlentities(mysqli_real_escape_string($shortageReportDB, $shipmentDateArray[$i]));
	$queryInsertData2 = "INSERT INTO ChepReport (
		transactionType, 
		otherParty, 
		quantity, 
		shipmentDate,
		yearMonthDate)
		VALUES (
		'$transactionTypeAray[$i]', 
		'$otherPartyArray[$i]',
		'$quantityArray[$i]', 
		'$shipmentDateArray[$i]',
		'$yearMonthDate'
		
		)";
	$resultInsertdata2 = mysqli_query($shortageReportDB, $queryInsertData2);
}

//---------------SELECT DATA FROM DATABASE---------------//
$querySelectData = "
	SELECT 
		otherParty, 
		SUM(quantity) AS monthlySum, 
		yearMonthDate 
	FROM ChepReport
		WHERE transactionType = 'Transfer In' 
	GROUP BY 
		otherParty, 
		yearMonthDate 
	ORDER BY
		otherParty";
$resulSelectData = mysqli_query($shortageReportDB, $querySelectData);
$num = mysqli_num_rows($resulSelectData);

//an array in which I will store the data arranged in manner to draw chart at front end
$resultArray = array();
if ($num>0){
	while ($row = mysqli_fetch_array($resulSelectData, MYSQLI_ASSOC))
	{	
		$supplierName = html_entity_decode($row['otherParty']);	
		$monthlySum = ROUND($row['monthlySum'],0);	
		$yearMonthDate = $row['yearMonthDate'];	
		
		//single associative array to store the data for month and quantity of pallets delivered
		$monthAndQuantity = array($yearMonthDate=>$monthlySum);
		
		if (!array_key_exists($supplierName, $resultArray)) {
			$resultArray += array($supplierName=>$monthAndQuantity);
		} else {
			$resultArray[$supplierName][$yearMonthDate]= $monthlySum;
		}
	}
} else {
	ReportErrorForEmptyExcelFile();
}


//------------------------------------------NEW CODE------------------------------------------------------------------//

//--a query to determine how many months of transfers have been uploaded
$queryCountMonth = "
	SELECT 
		COUNT(DISTINCT(yearMonthDate)) AS numberOfMonths
	FROM ChepReport
		WHERE transactionType = 'Transfer In'";
$resultCountMonth = mysqli_query($shortageReportDB, $queryCountMonth);
$num = mysqli_num_rows($resultCountMonth);

if ($num>0){
	while ($row = mysqli_fetch_array($resultCountMonth, MYSQLI_ASSOC))
	{	
		$numberOfMonths = $row['numberOfMonths'];
		
	}
} else {
	ReportErrorForEmptyExcelFile();
}

//an array to store month values
$distinctMonths = array();
//a query to select distinct month values 
$querySelectDistinctMonths = "
	SELECT 
		DISTINCT(yearMonthDate) AS distinctMonths
	FROM ChepReport
		WHERE transactionType = 'Transfer In'
	ORDER BY 
		yearMonthDate ASC";
$resultSelectDistinctMonths = mysqli_query($shortageReportDB, $querySelectDistinctMonths);
$num = mysqli_num_rows($resultSelectDistinctMonths);

if ($num>0){
	while ($row = mysqli_fetch_array($resultSelectDistinctMonths, MYSQLI_ASSOC))
	{	
		$monthValue = $row['distinctMonths'];
		array_push($distinctMonths, $monthValue);	
	}
} else {
	ReportErrorForEmptyExcelFile();
}

//in order to loop associative array I need distinct supplier names values
//an array to store month values
$distinctSuppliersNamesArray = array();
//a query to select distinct month values 
$querySelectDistinctSuppliersNames = "
	SELECT 
		DISTINCT(otherParty) AS distinctSupplierName
	FROM ChepReport
		WHERE transactionType = 'Transfer In'";
$resultSelectDistinctSuppliersNames = mysqli_query($shortageReportDB, $querySelectDistinctSuppliersNames);
$num = mysqli_num_rows($resultSelectDistinctSuppliersNames);

if ($num>0){
	while ($row = mysqli_fetch_array($resultSelectDistinctSuppliersNames, MYSQLI_ASSOC))
	{	
      	//has to be decoded !!!
		$distinctSupplierName = html_entity_decode($row['distinctSupplierName']);
		array_push($distinctSuppliersNamesArray, $distinctSupplierName);	
	}
} else {
	ReportErrorForEmptyExcelFile();
}

//CODE THAT FILLS 0 VALUES TO A RESULT ARRAY------------------------------------------------------//
//find the distinct number of suppliers, I could also do it by adding this to previuos SQL query
//COUNT(DISTINCT(otherParty)) AS numberOfSuppliers
//but I can extract the number of suppliers from the resultArray length.
$numberOfSuppliers = Count($resultArray );
//first I need to loop through all the suppliers
for ($i = 0; $i <$numberOfSuppliers; $i++){
	
	//in this loop I will check if each supplier has a quantity pallet for each month
	//I will do it by checking if an !array_key_exists for the month, if it does, 
	//nothing will be done, if it does not, a month value will be added as a key and 0 as it's value.
	for ($x = 0; $x <$numberOfMonths; $x++){
		if(!array_key_exists($distinctMonths[$x], $resultArray[$distinctSuppliersNamesArray[$i]])){
			$resultArray[$distinctSuppliersNamesArray[$i]][$distinctMonths[$x]]= 0;
		}		
	}
	//sort the the inner array within suppliers name distincsMonths in ascending order.
	ksort($resultArray[$distinctSuppliersNamesArray[$i]]);
}
//------------------------------------CHART 2---------------------------------------------------------------//
//store all possible transactions type names inside an array
$transferTypesNamesArray = array("Admin IN","Admin OUT","Correction IN","Returns","Reversed Transfer IN","Transfer IN","Transfer OUT", "Unknown");
//array to store values for chart 2
$transferTypseSumsArray = array();
//fill array with 0 values and transfer types as names
for ($i = 0; $i <Count($transferTypesNamesArray); $i++){
	$transferTypseSumsArray[$transferTypesNamesArray[$i]]= 0;
}
$palletsIN = 0;
$palletsOUT = 0;
$querySelectPalletSum = "
	SELECT 
		transactionType,
		SUM(quantity) AS palletSum
	FROM ChepReport
		WHERE 1
	GROUP BY
		transactionType
	ORDER BY
		transactionType
	";
$resultPalletSum= mysqli_query($shortageReportDB, $querySelectPalletSum);
$num = mysqli_num_rows($resultPalletSum);
if ($num>0){
	while ($row = mysqli_fetch_array($resultPalletSum, MYSQLI_ASSOC))
	{	
		$palletSum = ROUND($row['palletSum'],0);
		$transactionType = $row['transactionType'];
		//add pallet sum from row to either $palletsIN or PalletsOUT variable 
		if($palletSum > 0) {
			$palletsIN+=$palletSum;
			
		} else {
			$palletsOUT += $palletSum;
		}
		//for chart I dont want negative values
		$palletSum = abs($palletSum );
		$transferTypseSumsArray[$transactionType]=$palletSum;
		//$arrayIndex++;

	}
} else {
	ReportErrorForEmptyExcelFile();	
}

//for chart I don't want to have negative values
$palletsOUT = abs($palletsOUT );
//a query to select MIN and MAX dates for transfers/returns
$querySelectMinMaxDates = "
	SELECT 
		MIN(shipmentDate) AS startDate,
		MAX(shipmentDate) AS endDate
	FROM ChepReport
		WHERE transactionType IN('Returns', 'Transfer IN')";
$resultMinMaxDates= mysqli_query($shortageReportDB, $querySelectMinMaxDates);
$num = mysqli_num_rows($resultMinMaxDates);

if ($num>0){
	while ($row = mysqli_fetch_array($resultMinMaxDates, MYSQLI_ASSOC))
	{	
		$startDate = $row['startDate'];	
		$endDate = $row['endDate'];	
	}
} else {
	ReportErrorForEmptyExcelFile();
}

//----------------------------------------------------CODE FOR CHART 3---------------------------------------//
//loops to create data structure to store monthly sum of pallets grouped by transfer type
$dataForTransferTypeChart = array();
for ($i = 0; $i <Count($transferTypesNamesArray); $i++){
	for ($x = 0; $x <$numberOfMonths; $x++){		
		$dataForTransferTypeChart[$transferTypesNamesArray[$i]][$distinctMonths[$x]]= 0;
	}
}

//---------------SELECT DATA FROM DATABASE FOR CHART 3---------------//
$querySelectDataForChart3 = "
	SELECT 
		transactionType, 
		SUM(quantity) AS monthlySum, 
		yearMonthDate 
	FROM ChepReport
		WHERE 1=1 
	GROUP BY 
		transactionType, 
		yearMonthDate 
	ORDER BY
		yearMonthDate";
$resulSelectDataForChart3 = mysqli_query($shortageReportDB, $querySelectDataForChart3);
$num = mysqli_num_rows($resulSelectDataForChart3);

if ($num>0){
	while ($row = mysqli_fetch_array($resulSelectDataForChart3, MYSQLI_ASSOC))
	{	

		$transactionType = html_entity_decode($row['transactionType']);	
		//for a chart I don't want negative values, hence the abs() function is used here
		$monthlySum = abs(ROUND($row['monthlySum'],0));	

		$yearMonthDate = $row['yearMonthDate'];

		//update associative array value
		$dataForTransferTypeChart[$transactionType][$yearMonthDate]= $monthlySum;

	}
} else {
	ReportErrorForEmptyExcelFile();
}

//Delete values from the current table 
//After all quering is done I keep the database empty.
$queryDelete = "DELETE FROM ChepReport WHERE 1 = 1";
$resultDelete = mysqli_query($shortageReportDB, $queryDelete);

mysqli_close($shortageReportDB);
$arrayForFrontEnd += array("errors"=>$errors, "resultArray"=>$resultArray, "distinctMonths"=>$distinctMonths);
$arrayForFrontEnd += array("distinctSuppliersNamesArray"=>$distinctSuppliersNamesArray, "dataForTransferTypeChart"=>$dataForTransferTypeChart);
$arrayForFrontEnd += array("startDate"=>$startDate, "endDate"=>$endDate, "transferTypseSumsArray"=>$transferTypseSumsArray);
$arrayForFrontEnd += array("palletsIN"=>$palletsIN, "palletsOUT"=>$palletsOUT, "transferTypesNamesArray"=>$transferTypesNamesArray);

$jsonFile = json_encode($arrayForFrontEnd);
echo $jsonFile;

?>