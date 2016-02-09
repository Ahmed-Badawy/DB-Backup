<?php 

require("B_DBController.php");


$config_array = [
	"db_host" => "localhost",
	"db_name" => "shopjavaproject",
	"db_user" => "root",
	"db_pass" => ""
];
$obj = new B_DBController($config_array);

$output_dir  	= 	__dir__."/backups"; // directory files
$output_name 	= 	time()."-".$obj->db_name; // output name sql backup

//$output = $obj->backup_database($output_dir,$output_name)->generate_download_file();
$output = $obj->backup_database($output_dir,$output_name)->save_gzip_file();
// $output = $obj->backup_database($output_dir,$output_name)->save_sql_file();
// $obj->show_all_tables();




?>