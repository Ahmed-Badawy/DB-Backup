<?php

class B_DBController{
    public $db_host = 'localhost';
    public $db_name;
    public $db_user = 'root';
    public $db_pass = '';

    public $backup_data = [];

    public function __construct($array){
        $this->db_name = $array['db_name'];
        $this->db_host = $array['db_host'];
        $this->db_user = $array['db_user'];
        $this->db_pass = $array['db_pass'];
    }


/*********************************************************************
    Exporting Functions
 **********************************************************************/

//just provide the imaginary file type
    public function generate_download_file(){
        $name = $this->backup_data['name'];
        $dir = $this->backup_data['dir'];
        $sql_txt = $this->basic_sql_file_layout();
        $fullname = $dir . '/' . $name . '.sql.gz'; # full structures
        @ini_set('zlib.output_compression', 'Off');
        $gzipoutput = gzencode($sql_txt, 9);

        // various headers, those with # are mandatory
        header('Content-Type: application/x-download');
        header("Content-Description: File Transfer");
        header('Content-Encoding: gzip'); #
        header('Content-Length: '.strlen( $gzipoutput ) );
        header('Content-Disposition: attachment; filename="'.$name.'.sql.gz'.'"');
        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Connection: Keep-Alive');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        echo $gzipoutput;
    }
    public function save_gzip_file(){
        $name = $this->backup_data['name'];
        $dir = $this->backup_data['dir'];
        $sql_txt = $this->basic_sql_file_layout();
        $fullname = $dir . '/' . $name . '.sql.gz'; # full structures
        @ini_set('zlib.output_compression', 'Off');
        $gzipoutput = gzencode($sql_txt, 9);
        if (@file_put_contents($fullname, $gzipoutput)) { # 9 as compression levels
            $result = $name . '.sql.gz';
            return $result; # show the name
        } else { # if could not put file , automaticly you will get the file as downloadable
            $result = false;
            $this->generate_download_file($name,$gzipoutput);
            return $result;
        }
    }
    public function save_sql_file(){
        $name = $this->backup_data['name'];
        $dir = $this->backup_data['dir'];
        $sql_txt = $this->basic_sql_file_layout();
        $fullname = $dir . '/' . $name . '.sql'; # full structures
        if (@file_put_contents($fullname, $sql_txt)) { # 9 as compression levels
            $result = $name . '.sql';
            return $result; # show the name
        } else { # if could not put file , automaticly you will get the file as downloadable
            $result = false;
            $this->generate_download_file($name,$sql_txt);
            return $result;
        }
    }





    private function basic_sql_file_layout(){
        $tables_backup_array = $this->backup_data["tables_array"];
        $mysqli = $this->backup_data['mysqli'];

        $return =
"-- ---------------------------------------------------------
--
-- Database: ".$this->db_name."
-- Host Connection Info: ".$mysqli->host_info."
-- Generation Time: ".date('F d, Y \a\t H:i A ( e )')."
-- Server version: ".$mysqli->get_server_info()."
-- PHP Version: ".PHP_VERSION."
--
-- ---------------------------------------------------------\n\n

SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
SET time_zone = \"+00:00\";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
".implode("\n\n",$tables_backup_array)."
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
# end values result
        return $return;
    }




/**
 * MYSQL EXPORT TO GZIP
 * exporting database to sql gzip compression data.
 * if directory writable will be make directory inside of directory if not exist, else wil be die
 *
 * @param string directory , as the directory to put file
 * @param $outname as file name just the name !, if file exist will be overide as numeric next ++ as name_1.sql.gz , name_2.sql.gz next ++
 *
 * @param string $dbhost database host
 * @param string $dbuser database user
 * @param string $dbpass database password
 * @param string $dbname database name
 *
 */
public function backup_database($directory,$outname) {
        $return_tables_array = [];

        // check mysqli extension installed
        if( ! function_exists('mysqli_connect') ) {
            die(' This scripts need mysql extension to be running properly ! please resolve!!');
        }
        $mysqli = @new mysqli($this->db_host,$this->db_user, $this->db_pass,$this->db_name);
        $mysqli->set_charset("utf8");
        if( $mysqli->connect_error ) {
            print_r( $mysqli->connect_error );
            return false;
        }

    $dir = $directory;
        $result = '<p> Could not create backup directory on :'.$dir.' Please Please make sure you have set Directory on 755 or 777 for a while.</p>';
        $res = true;
        if( ! is_dir( $dir ) ) {
            if( ! @mkdir( $dir, 755 )) {
                $res = false;
            }
        }
        $n = 1;
        if( $res ) {
            $name = $outname;
            # counts
            if( file_exists($dir.'/'.$name.'.sql.gz' ) ){
                for($i=1;@count( file($dir.'/'.$name.'_'.$i.'.sql.gz') );$i++){
                    $name = $name;
                    if( ! file_exists( $dir.'/'.$name.'_'.$i.'.sql.gz') ) {
                        $name = $name.'_'.$i;
                        break;
                    }
                }
            }
//            $fullname = $dir.'/'.$name.'.sql.gz'; # full structures
            if( ! $mysqli->error ) {
                $sql = "SHOW TABLES";
                $show = $mysqli->query($sql);
                while ( $r = $show->fetch_array() ) {
                    $tables[] = $r[0];
                }
                if( ! empty( $tables ) ) {
                    //cycle through
                    $return = '';
                    foreach( $tables as $table ){
                        $result = $mysqli->query('SELECT * FROM '.$table);
                        $num_fields = $result->field_count;
                        $row2       = $mysqli->query('SHOW CREATE TABLE '.$table );
                        $row2       = $row2->fetch_row();
                        $return    .=
                            "\n-- ---------------------------------------------------------
-- Table structure for table : `{$table}`
-- ---------------------------------------------------------
".$row2[1].";\n";

                        for ($i = 0; $i < $num_fields; $i++){
                            $n = 1 ;
                            while( $row = $result->fetch_row() ){
                                if( $n++ == 1 ) { # set the first statements
                                    $return .=
                                        "
-- -------------------------------
-- Dumping data for table `{$table}`
-- -------------------------------

";

                                    /**
                                     * Get structural of fields each tables
                                     */
                                    $array_field = array(); #reset ! important to resetting when loop
                                    while( $field = $result->fetch_field() ) # get field
                                    {
                                        $array_field[] = '`'.$field->name.'`';
                                    }
                                    $array_f[$table] = $array_field;
                                    // $array_f = $array_f;
                                    # endwhile
                                    $array_field = implode(', ', $array_f[$table]); #implode arrays

                                    $return .= "INSERT INTO `{$table}` ({$array_field}) VALUES\n(";
                                } else $return .= '(';

                                for($j=0; $j<$num_fields; $j++){
                                    $row[$j] = str_replace('\'','\'\'', preg_replace("/\n/","\\n", $row[$j] ) );
                                    if ( isset( $row[$j] ) ) { $return .= is_numeric( $row[$j] ) ? $row[$j] : '\''.$row[$j].'\'' ; } else { $return.= '\'\''; }
                                    if ($j<($num_fields-1)) { $return.= ', '; }
                                }
                                $return.= "),\n";
                            }
                            # check matching
                            @preg_match("/\),\n/", $return, $match, false, -3); # check match
                            if( isset( $match[0] ) ) $return = substr_replace( $return, ";\n", -2);
                        }
                        $return .= "-- ------------- End Of Table: $table ------------\n";
                        $return .= "-- ------------------------------------------------\n";
                        $return_tables_array[$table] = $return;
                        $return = "";
                    }

                    $this->backup_data = [
                        "dir"=>$dir,
                        "name"=>$name,
                        "mysqli"=>$mysqli,
                        "tables_array"=>$return_tables_array
                    ];
                    return $this;

//                    $output = $this->basic_sql_file_layout($return_tables_array,$mysqli);
//                    echo "<pre>";
//                    var_export($return_tables_array);
//                    die;

                } else {
                    $result = '<p>Error when executing database query to export.</p>'.$mysqli->error;
                }
            }
        } else {
            $result = '<p>Wrong mysqli input</p>';
        }
        if( $mysqli && ! $mysqli->error ) {
            @$mysqli->close();
        }
        return $result;
    }





/*********************************************************************
    Other Functions
**********************************************************************/
function drop_all_tables(){
  $mysqli = new mysqli($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
  $mysqli->query('SET foreign_key_checks = 0');
  if ($result = $mysqli->query("SHOW TABLES")){
    while($row = $result->fetch_array(MYSQLI_NUM)){
        $mysqli->query('DROP TABLE IF EXISTS `'.$row[0]."`");
    }
  }
  $mysqli->query('SET foreign_key_checks = 1');
}
function show_all_tables(){
  $mysqli = new mysqli($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
  $mysqli->query('SET foreign_key_checks = 0');
  if ($result = $mysqli->query("SHOW TABLES")){
    while($row = $result->fetch_array(MYSQLI_NUM)){
      echo $row[0]."\n<br>";
    }
  }
  $mysqli->query('SET foreign_key_checks = 1');
}




/*********************************************************************
    Importing Functions
 **********************************************************************/
    /**
     * Function to build SQL /Importing SQL DATA
     *
     * @param string $sql_string as the queries of sql data , yopu could use file get contents to read data args
     * @param string $dbhost database host
     * @param string $dbuser database user
     * @param string $dbpass database password
     * @param string $dbname database name
     *
     * @return string complete if complete
     */
    function mysqli_import_sql($sql_string) {
//        $dbname = $this->db_name;
//        $dbhost = $this->db_host;
//        $dbuser = $this->db_user;
//        $dbpass = $this->db_pass;

        // check mysqli extension installed
        if( ! function_exists('mysqli_connect') ) {
            die(' This scripts need mysql extension to be running properly ! please resolve!!');
        }

        $mysqli = @new mysqli($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
        $mysqli->set_charset("utf8");


        if( $mysqli->connect_error ) {
            print_r( $mysqli->connect_error );
            return false;
        }

        $querycount = 11;
        $queryerrors = '';
        $lines = (array) $sql_string;
        if( is_string( $sql_string ) ) {
            $lines =  array( $sql_string ) ;
        }

        if ( ! $lines ) {
            return '' . 'cannot execute ' . $sql_string;
        }

        $scriptfile = false;
        foreach ($lines as $line) {
            $line = trim( $line );
            // if have -- comments add enters
            if (substr( $line, 0, 2 ) == '--') {
                $line = "\n" . $line;
            }
            if (substr( $line, 0, 2 ) != '--') {
                $scriptfile .= ' ' . $line;
                continue;
            }
        }

        $queries = explode( ';', $scriptfile );
        foreach ($queries as $query) {
            $query = trim( $query );
            ++$querycount;

            if ( $query == '' ) {
                continue;
            }


            if ( ! $mysqli->query( $query ) ) {
                $queryerrors .= '' . 'Line ' . $querycount . ' - ' . $mysqli->error . '<br>';
                continue;
            }
        }
        if ( $queryerrors ) {
            return '' . 'There was an error on the SQL String: <br>' . $queryerrors;
        }
        if( $mysqli && ! $mysqli->error ) {
            @$mysqli->close();
        }

        return 'complete dumping database !';
    }


}




//$config_array = [
//    "db_host" => "localhost",
//    "db_name" => "shopjavaproject",
//    "db_user" => "root",
//    "db_pass" => ""
//];
//$obj = new B_DBController($config_array);

//$output_dir  	= 	__dir__."/backups"; // directory files
//$output_name 	= 	time()."-".$obj->db_name; // output name sql backup

//$output = $obj->backup_database($output_dir,$output_name)->generate_download_file();
//$output = $obj->backup_database($output_dir,$output_name)->save_gzip_file();
// $output = $obj->backup_database($output_dir,$output_name)->save_sql_file();
// $obj->show_all_tables();




