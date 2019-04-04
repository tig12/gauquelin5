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
    $res .= "\n" . '<table">';
    $res .= "\n<tr><th>Code</th><th>Label (fr)</th><th>Label (en)</th></tr>";
    foreach($codes as $code => $labels){
        $res .= "\n<tr><td>$code</td><td>{$labels[0]}</td><td>{$labels[1]}</td></tr>";
    }
    $res .= "\n</table>";
    echo $res . "\n";
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
    @return associative array profession code => [profession label fr, profession label en]
**/
function read_input_file(){
    $lines = file(dirname(__DIR__) . DS . 'share' . DS . 'profession-codes.csv');
    $res = [];
    $check = [];
    foreach($lines as $line){
        $line = trim($line);
        if($line == '' || substr($line, 0, 1) == '#'){
            continue;
        }
        $cur = explode(';', $line);
        $code = $cur[0];
        if(!isset($check[$code])){
            $check[$code] = 0;
        }
        $check[$code]++;
        $res[$code] = [$cur[1], $cur[2]];
    }
    // check doublons
    foreach($check as $code => $n){
        if($n > 1){
            die("ERROR : code '$code' appears more than once\n");
        }
    }
    //
    return $res;
}
