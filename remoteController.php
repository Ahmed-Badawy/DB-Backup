<?php 

$input = $_POST;


require("B_DBController.php");

$config_array = [
	"db_host" => $input['host'],
	"db_name" => $input['db'],
	"db_user" => $input['user'],
	"db_pass" => $input['pass']
];
$obj = new B_DBController($config_array);

$output_dir  	= 	__dir__."/backups"; // directory files
$output_name 	= 	time()."-".$obj->db_name; // output name sql backup


//var_export($config_array);die;


if($input['type']=="download") $output = $obj->backup_database($output_dir,$output_name)->generate_download_file();
if($input['type']=="save_gzip") $output = $obj->backup_database($output_dir,$output_name)->save_gzip_file();
if($input['type']=="save_sql") $output = $obj->backup_database($output_dir,$output_name)->save_sql_file();
if($input['type']=="test") $output = $obj->backup_database($output_dir,$output_name)->backup_data;
// $obj->show_all_tables();


echo "<pre>";
var_export($output);
die;


?>