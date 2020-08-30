<?php
/******************************************************************************
    Code to generate lists of profession codes and their names
    
    usage :
    - Generate a markdown table :
    php profession-codes.php md
    - Generate a html table
    php profession-codes.php html
    
    @license    GPL
    @copyright  Thierry Graff
    @history    2017-05-03 10:29:04+02:00, Thierry Graff : Creation
    @history    2019-05-28 16:06:12+02:00, Thierry Graff : Replace csv version by yaml
********************************************************************************/

define('DS', DIRECTORY_SEPARATOR);

$commands = [
    'html',
    'md',
];
$commands_str = implode("' or '", $commands);

$USAGE = <<<USAGE
usage : 
    php {$argv[0]} <command>
with :
    <command> = '{$commands_str}'
Exemples :
    php {$argv[0]} md        # generates the markdown table

USAGE;

// check arguments
if(count($argv) < 2){
    die($USAGE);
}

// check command
$command = $argv[1];
if(!in_array($command, $commands)){
    echo "!!! INVALID COMMAND !!! : '$command' - possible choices : '$commands_str'\n";
    exit;
}

//
// run
//
switch($command){
	case 'html' : html_table(); break;
	case 'md' : md_table(); break;
}


//
// General commands
//

// ******************************************************
/**
    Generates a table of profession codes in html
**/
function html_table(){
    $codes = read_input_file();    
    $res = '';
    // General codes
    $res .= "\n<!-- ************************************* -->\n";
    $res .= "<h3>General codes</h3>\n";
    $res .= "<table class=\"wikitable margin\">\n";
    $res .= "<tr><th>Code</th><th>Label (fr)</th><th>Label (en)</th></tr>\n";
    foreach($codes as $code => $record){
        $belongs = belongs_to($record);
        if(!empty($belongs)){
            continue;
        }
        $res .= "<tr><td>$code</td><td>{$record['fr']}</td><td>{$record['en']}</td></tr>\n";
    }
    $res .= "</table>\n";
    // Artists
    $res .= "\n<!-- ************************************* -->\n";
    $res .= "<h3>Artists</h3>\n";
    $res .= "<table class=\"wikitable margin\">\n";
    $res .= "<tr><th>Code</th><th>Label (fr)</th><th>Label (en)</th></tr>\n";
    foreach($codes as $code => $record){
        $belongs = belongs_to($record);
        if(!in_array('AR', $belongs)){
            continue;
        }
        $res .= "<tr><td>$code</td><td>{$record['fr']}</td><td>{$record['en']}</td></tr>\n";
    }
    $res .= "</table>\n";
    // Sportsmen
    $res .= "\n<!-- ************************************* -->\n";
    $res .= "<h3>Sports</h3>\n";
    $res .= "<table class=\"wikitable margin\">\n";
    $res .= "<tr><th>Code</th><th>Label (fr)</th><th>Label (en)</th></tr>\n";
    foreach($codes as $code => $record){
        $belongs = belongs_to($record);
        if(!in_array('SP', $belongs)){
            continue;
        }
        $res .= "<tr><td>$code</td><td>{$record['fr']}</td><td>{$record['en']}</td></tr>\n";
    }
    $res .= "</table>\n";
    echo $res;
}


// ******************************************************
/**
    Generates a table of profession codes in markdown format (tested on github.com)
**/
function md_table(){
    $codes = read_input_file();
    ksort($codes);
    $res = '';
    $res .= "\n| Code | Label (fr) | Label (en) |";
    $res .= "\n| --- | --- | --- |";
    foreach($codes as $code => $labels){
        $res .= "\n| $code | {$labels[0]} | {$labels[1]} | ";
    }
    echo $res . "\n";
}


//
// Auxiliary functions
//

// ******************************************************
/**
    Loads file profession-codes
    If a code appears more than once, the program exits with an error message
    Otherwise, file supposed correct, no check done
    @return associative array profession code => content of this record
**/
function read_input_file(){
    $records = yaml_parse_file(implode(DS, [dirname(__DIR__), 'data', 'db', 'occu.yml']));
    
    $res = [];
    $check = [];
    foreach($records as $rec){
        $code = $rec['code'];
        if(!isset($check[$code])){
            $check[$code] = 0;
        }
        $check[$code]++;
        $res[$code] = $rec;
    }
    // check doublons
    foreach($check as $code => $n){
        if($n > 1){
            die("ERROR : code '$code' appears more than once\n");
        }
    }
    //
    ksort($res);
    return $res;
}


// ******************************************************
/**
    @param $record One element of the yaml file, representing an occupation
**/
 function belongs_to($record){
     return $record['belongs-to'] ?? [];
}
