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

	echo $ordersCsv;
	if($ordersCsv != ""){
		writeCSV($ordersCsv, $csvPath);
		writeDate($datePath);
	}
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
	if(curl_exec($ch) === false)
	{
		echo 'Curl-Fehler: ' . curl_error($ch);
	}
	curl_close($ch);

	//Convert the XML result into array
	$array_data = json_decode(json_encode(simplexml_load_string($data, null, LIBXML_NOCDATA)), true);

	/*
	print_r('<pre>');
	print_r($array_data);
	print_r('</pre>');
	*/
	
	return $array_data;
}
	
	
	
/*
 * Converts the apis xml to csv
 * 
 * @input array    $array_data    The array that got generated from the apis xml. We use getOrders() to generate this variable.
 * @return string   $csv    The csv files content.
*/	
function convertXMLtoCSV($array_data){
	$csv = "";	

	if(sizeof($array_data["orders"]["order"]) != 4){
		$array_data_order = $array_data["orders"]["order"];
	}
	else{
		$array_data_order = $array_data["orders"];
	}


	foreach($array_data_order as $currentOrder){
		
		/*
		print_r('<pre>');
		print_r($currentOrder);
		print_r('</pre>');
		echo "SIZE: " . sizeof($currentOrder["orderItems"]["item"]);

		echo "<br><br><br>";
		*/
		

		//If the customer orders exactly 10 or 11 differnt items, we will get an error :)
		//Sometimes we get 10 and sometimes 11 as result of item size, because mpn is not always filled
		if(sizeof($currentOrder["orderItems"]["item"]) == 9 || sizeof($currentOrder["orderItems"]["item"]) == 10 || sizeof($currentOrder["orderItems"]["item"]) == 11){
			$trueItemSize = 1;
		}
		else{
			$trueItemSize = sizeof($currentOrder["orderItems"]["item"]);
		}
		
		//Cycle throught all items
		for($i = 0; $i < $trueItemSize; $i++){
			$orderDetails = $currentOrder["details"];
			$orderBuyer = $currentOrder["buyer"];
			$orderItem = $currentOrder["orderItems"]["item"];
			$orderShip = $currentOrder["shipAddress"];
	
			$csv = $csv . $orderDetails["orderID"] . ";";
			$csv = $csv . $orderDetails["date"] . ";";
			$csv = $csv . $orderBuyer["email"] . ";";
			if($trueItemSize == 1){
				$csv = $csv . $orderItem["itemNumber"] . ";";
				$csv = $csv . $orderItem["quantity"] . ";";
				$csv = $csv . $orderItem["price"]  . ";";
			}
			else{
				$csv = $csv . $orderItem[$i]["itemNumber"] . ";";
				$csv = $csv . $orderItem[$i]["quantity"] . ";";
				$csv = $csv . $orderItem[$i]["price"]  . ";";
			}			
			$csv = $csv . $orderShip["firstName"] . " " . $orderShip["lastName"] . ";";
			$csv = $csv . ";";
			$csv = $csv . $orderShip["address"] . ";";
			$csv = $csv . $orderShip["zip"] . ";";
			$csv = $csv . $orderShip["city"] . ";";
			$csv = $csv . $orderShip["countryTwoDigit"] . ";";
			$csv = $csv . $orderBuyer["firstName"] . " " . $orderBuyer["lastName"] . ";";
			$csv = $csv . ";";
			$csv = $csv . $orderBuyer["address"] . ";";
			$csv = $csv . $orderBuyer["zip"] . ";";
			$csv = $csv . $orderBuyer["city"] . ";";
			$csv = $csv . $orderBuyer["countryTwoDigit"] . ";";
			$csv = $csv . ";";
			//If status is "Ausstehend", set the paymentTypeCode to unpaid
			if($orderDetails["orderStatusBuyer"] == "Ausstehend"){
				$csv = $csv . "Unpaid;";
			}
			else{
				$csv = $csv . $orderDetails["paymentTypeCode"] . ";";
			}
			
			$csv = $csv . $orderDetails["shipCost"] . ";";
			$csv = $csv . $orderDetails["paymentTransactionID"] . "";
			$csv = $csv . "\r\n";	
		}
		
	}

	if($csv != ""){
		$csv = generateCSVHeadline() . $csv;
	}
	
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
			. "Phone;PaymentType;Shipping;TransactionId;Username" . "\r\n";
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
