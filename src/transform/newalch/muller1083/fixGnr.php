<?php
/********************************************************************************
    Builds Muller1083::GNR_FIX
    
    Code to examine 5-tmp/newalch-csv/1083MED.csv
    (not the raw file, the intermediate file generated by raw2csv.php).
    Not part of any build process - only to try to understand.
    
    
    @license    GPL
    @history    2019-10-15 01:24:44+02:00, Thierry Graff : creation
********************************************************************************/
namespace g5\transform\newalch\muller1083;

use g5\patterns\Command;
use g5\transform\cura\Cura;

class fixGnr implements Command {
    
    // *****************************************
    /** 
        Routes to the different actions, based on $param
        @param $param Array containing one element (a string)
                      Must be one of self::POSSIBLE_PARAMS
    **/
    public static function execute($params=[]): string{
/* 
        if(count($params) == 0){
            return "PARAMETER MISSING in g5\\transform\\newalch\\muller1083\\look\n"
                . "Possible values for parameter : $possibleParams_str\n";
        }
        $param = $params[0];
        if(!in_array($param, self::POSSIBLE_PARAMS)){
            return "INVALID PARAMETER in g5\\transform\\newalch\\muller1083\\look\n"
                . "Possible values for parameter : $possibleParams_str\n";
        }
*/
        $log = '';
        $res = "    const FIX_GNR = [\n";
        // Build an array containing duplicate values of GNR in 1083MED.csv
        $rows = Muller1083::loadTmpFile();
        $tmp = [];
        foreach($rows as $row){
            $gnr = $row['GNR'];
            if($gnr == ''){
                continue;
            }
            if(!isset($tmp[$gnr])){
                $tmp[$gnr] = [];
            }
            $tmp[$gnr][] = [
                'NR' => $row['NR'],
                'FNAME' => $row['FNAME'],
                'GNAME' => $row['GNAME'],
                'DATE' => $row['DATE'],
            ];
        }
        $mullers = [];
        // keep only duplicates
        foreach($tmp as $gnr => $val){
            if(count($val) == 1){
                continue; // no duplicates, ok
            }
            $mullers[$gnr] = $val;
        }
        
        // Load A2 and E1 ; assoc arrays, keys = NUM
        $a2s = Cura::loadTmpCsv_num('A2');
        $e1s = Cura::loadTmpCsv_num('E1');
        
        // match muller records with target records in A2 or E3, for example :
        // $gnr = 103 => targets have NUM = 103, 1030, 1031 ... 1039
        
        foreach($mullers as $GNR => $muller){
            $curaPrefix = substr($GNR, 0, 3); // SA2 or ND1
            if($curaPrefix == 'SA2'){
                $curaFile =& $a2s;
//continue;
            }
            else{
                $curaFile =& $e1s;
            }
            
            $NUM_muller = substr($GNR, 3);
            $targetNUMs = self::targetNums($NUM_muller);
            
            // check if ambiguity on date in muller records
            // (result = no ambiguity)
            $dates_muller = [];
// echo "GNR = $GNR\n";
            foreach($muller as $rec){
                $date = substr($rec['DATE'], 0, 10); // compare only days
                if(in_array($date, $dates_muller)){
                    echo "DUPLICATE DATE IN MULLER : GNR = $GNR - DATE = $date\n";
                    echo "Case not fixed\n";
                    continue 2; // next muller record
                }
                $dates_muller[] = $date;
            }
            
            foreach($muller as $rec_muller){
                $NR = $rec_muller['NR'];
                $date_muller = substr($rec_muller['DATE'], 0, 10);
// echo "\n<pre>"; print_r($rec_muller); echo "</pre>\n";
// echo "date_muller = '$date_muller'\n";
                $candidates = [];
                $curaTests = []; // only useful to log NO MATCHING case
                foreach($targetNUMs as $targetNUM){
//echo "\n<pre>"; print_r($curaFile); echo "</pre>\n"; exit;
                    $date_cura = substr($curaFile[$targetNUM]['DATE'], 0, 10);
                    $curaTests[$targetNUM] = $date_cura;
//echo "targetNUM $targetNUM - date_cura = '$date_cura'\n";
                    if($date_muller == $date_cura){
                        $candidates[] = $targetNUM;
                    }
                }
                if(count($candidates) == 0){
                    echo "NO MATCHING for Muller NR = $NR - GNR = $GNR\n";
                    echo "    Muller : $date_muller | {$rec_muller['FNAME']} | {$rec_muller['GNAME']}\n";
                    echo "    Possible Cura :\n";
                    foreach($curaTests as $k => $v){
                        echo "        $k : $v | {$curaFile[$k]['FNAME']} | {$curaFile[$k]['GNAME']}\n";
                    }
                    continue; // next muller rec
                }
                if(count($candidates) > 1){
                    echo "AMBIGUITY for Muller NR = $NR - GNR = $GNR\n";
                    echo "    Possible matches in cura file : " . implode($candidates, ', ') . "\n";
                    continue; // next muller rec
                }
//echo "\n<pre>"; print_r($candidates); echo "</pre>\n";
                // found a match
                $res .= "        $NR => $curaPrefix{$candidates[0]},\n";
                
            }
//exit;
// echo "$res\n";
        }
        $res.= "    ];\n";
        return '';
        return $res;
    }
    
    // ******************************************************
    /**
        Auxiliary of execute()
        Given a NUM in muller file, computes the possible matching NUM in cura files.
        ex : $NUM = 103 => returns [103, 1030, 1031 ... 1039]
        @param $NUM Muller NUM, computed from GNR
    **/
    private static function targetNums($NUM){
        $res = [$NUM];
        $x10 = $NUM * 10;
        for($i=0; $i < 10; $i++){
            $res[] = $x10 + $i;
        }
        return $res;
    }
    
    
}// end class