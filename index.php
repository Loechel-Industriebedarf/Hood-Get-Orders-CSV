<?php

require_once 'settings.php';

/* 
 * Check, if the csv exists.
 * If it doesn't exist: Download recent xml from the hood api
 * and parse it to csv. 
*/
if(!file_exists($csvPath)){
	$orders = getOrders($username, $pw_hash, $lastDateStr);
	$ordersCsv = convertXMLtoCSV($orders);
	//writeCSV($ordersCsv, $csvPath);
	writeDate($datePath);
}
else{
	echo "CSV file was not processed yet!";
}





/*
 * Gets the orders and converts their xml to an array.
 * 
 * @input string	$asdf	First input
 * @return array   $array_data    The apis response as array.
*/
function getOrders($username, $pw_hash, $lastDateStr){
	//Store your XML Request in a variable
    $input_xml = '
	<?xml version="1.0" encoding="UTF-8"?>
	<api type="public" version="2.0.1" user="'. $username . '" password="' . $pw_hash . '">
		<accountName>'. $username . '</accountName>
		<accountPass>' . $pw_hash . '</accountPass>	
		<function>orderList</function>			
		
		<dateRange>
			<type>orderDate</type> 		
			<startDate>' . $lastDateStr . '</startDate>		
			<endDate></endDate>		
		</dateRange>
		<orderID></orderID>		
	</api>
	';

	$url = "https://www.hood.de/api.htm";

	//Setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Following line is compulsary to add as it is:
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input_xml);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
	$data = curl_exec($ch);
	curl_close($ch);

	//Convert the XML result into array
	$array_data = json_decode(json_encode(simplexml_load_string($data)), true);

	print_r('<pre>');
	print_r($array_data);
	print_r('</pre>');
	
	convertXMLtoCSV($array_data);
}
	
	
	
/*
 * Converts the apis xml to csv
 * 
 * @input array    $array_data    The array that got generated from the apis xml. We use getOrders() to generate this variable.
 * @return string   $csv    The csv files content.
*/	
function convertXMLtoCSV($array_data){
	$csv = generateCSVHeadline();
	
	//TODO: Convert to csv
	return $csv;
}



/*
 * Generates the headline of the csv file.
 * 
 * @return string   $csv_headline    The headline for the csv file.
*/
function generateCSVHeadline(){
    $csv_headline = ""
            . "OrderNumber;OrderDate;EMail;"
            . "ArticleNumber;ArticleQuantity;ArticlePrice;"
            . "DeliveryClient;DeliveryClient2;DeliveryStreet;"
            . "DeliveryZIP;DeliveryCity;DeliveryCountry;"
            . "InvoiceClient;InvoiceClient2;InvoiceStreet;"
            . "InvoiceZIP;InvoiceCity;InvoiceCountry;"
            . "Phone;PaymentType;Shipping";
    return $csv_headline;
}



/*
 * Creates a csv file from a string.
 * 
 * @input string    $csv    Csv content, that should be written to a file
*/
function writeCsv($csv, $csvPath){
    echo $csv;
    
    $fp = fopen($csvPath, 'w');
    fwrite($fp, $csv);
    fclose($fp);
}



/*
 * Writes the date to file.
*/
function writeDate($datePath){
    $date = new DateTime();
    
    $fp = fopen($datePath, 'w');
    fwrite($fp, $date->format('Y-m-d H:i:s'));
    fclose($fp);
}
