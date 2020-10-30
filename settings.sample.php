<?php

/* 
 * Some variables
 */

$csvPath = "..\hoodOrder.csv";
$datePath = "date.txt";

date_default_timezone_set('Europe/Berlin');

$lastDate = readDate($datePath);
$lastDateStr = $lastDate->format('Y-m-d H:i:s');



function readDate($datePath){
    $datetime = file_get_contents($datePath); 
    $date = new DateTime($datetime);
    return $date;
}