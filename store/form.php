<?php

require_once __DIR__ . '/includes/init.php';

if(isset($_POST['submit'])){
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    $headers = fgetcsv($handle, 1000, ",");
    $sku = [];
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
    {
        $sku[] = $data[0];
    }
    fclose($handle);
    $sku = array_unique($sku);


    dd($sku);
}
?>


<!--
Create a form which can upload a csv file
-->


