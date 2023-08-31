<?php
date_default_timezone_set("Europe/London");
error_reporting( E_ALL );
  ini_set( "display_errors", 0 ); 
//fetch data from front end, two arrays are sent of product codes and quantities
$resultArray = array();
$errors = array();

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
	$resultArray += array("errors"=>$errors);
	Die ($jsonFile = json_encode($resultArray));
}

require('../shortageReport_connectDB.php');

//Delete values from the current table 
//this needs to be done to avoid duplicate data
$queryDelete = "DELETE FROM ChepReport WHERE 1 = 1";

$resultDelete = mysqli_query($shortageReportDB, $queryDelete);
//find the length of array;
$arrayLength = Count($transactionTypeAray );

//Insert data into database by using for loop.
for ($i = 0; $i <$arrayLength; $i++){

	//I want to extract year and month from date as I intend to group results by month
	$splitShipmentDateArray= explode("-", $shipmentDateArray[$i]);
	//compose proper date
	$yearMonthDate = $splitShipmentDateArray[0].''.$splitShipmentDateArray[1];



	//since suppliers name might contain characters that interfare with insert into query, need to make sure this is avoided.
	//$otherPartyArray[$i] = htmlentities(mysqli_real_escape_string($shortageReportDB, $otherPartyArray[$i]));
	//I could check other values as well, but might do that later.
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

//SELECT otherParty, SUM(quantity), yearMonthDate FROM `ChepReport` where `transactionType` = "Transfer In" GROUP by otherParty, yearMonthDate order by otherParty


mysqli_close($shortageReportDB);

$resultArray += array("errors"=>$errors, "arrayLength"=>$arrayLength, "transactionTypeAray"=>$transactionTypeAray, "otherPartyArray"=>$otherPartyArray);
$resultArray += array("quantityArray"=>$quantityArray, "shipmentDateArray"=>$shipmentDateArray);

$jsonFile = json_encode($resultArray);
echo $jsonFile;

?>