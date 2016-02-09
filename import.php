<?php

require("B_DBController.php");


$config_array = [
	"db_host" => "localhost",
	"db_name" => "test",
	"db_user" => "root",
	"db_pass" => ""
];
$obj = new B_DBController($config_array);

	$sql_file_name 	= 	"1.sql";
	$full_directory	= 	__dir__."/backups/".$sql_file_name; 

	$sql_string = file_get_contents($full_directory); // get contents
//	echo "<pre>";var_export($sql_string);die;
	$obj->drop_all_tables();
	$obj->mysqli_import_sql($sql_string);







