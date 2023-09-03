<?php
date_default_timezone_set("Europe/London");
error_reporting( E_ALL );
  ini_set( "display_errors", 1 ); 
//fetch data from front end, two arrays are sent of product codes and quantities
$arrayForFrontEnd = array();
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
	$arrayForFrontEnd += array("errors"=>$errors);
	Die ($jsonFile = json_encode($arrayForFrontEnd));
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
	//$note = html_entity_decode($row4['note']); - to decode htmlentities
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
		$supplierName = $row['otherParty'];	
		$monthlySum = $row['monthlySum'];	
		$yearMonthDate = $row['yearMonthDate'];	
		
		//single associative array to store the data for month and quantity of pallets delivered
		$monthAndQuantity = array($yearMonthDate=>$monthlySum);
		
		if (!array_key_exists($supplierName, $resultArray)) {
			$resultArray += array($supplierName=>$monthAndQuantity);
		} else {
			array_push($resultArray[$supplierName], $monthlySum);
		}
	}
} else {
	$errors[] = 'Error! Please check that rows in excel file are not empty.';
	$arrayForFrontEnd += array("errors"=>$errors);
	Die ($jsonFile = json_encode($arrayForFrontEnd));	
}

mysqli_close($shortageReportDB);

$arrayForFrontEnd += array("errors"=>$errors, "test"=>$resultArray);

$jsonFile = json_encode($arrayForFrontEnd);
echo $jsonFile;

?>