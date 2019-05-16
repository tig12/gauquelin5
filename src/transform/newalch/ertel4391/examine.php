<?php
/********************************************************************************
    Code to examine 5-tmp/newalch-csv/4391SPO.csv
    (not the raw file, the intermediate file generated by raw2csv.php).
    Not part of any build process - only to try to understand.
    
    To add a new function : 
        - add entry in POSSIBLE_PARAMS
        - implement a method named "examine_<entry>"
    
    @license    GPL
    @history    2019-05-11 18:58:50+02:00, Thierry Graff : creation
********************************************************************************/
namespace g5\transform\newalch\ertel4391;

use g5\init\Config;
use g5\patterns\Command;

class examine implements Command {
    
    /** 
        Possible values of the command, for ex :
        php run.php newalch ertel4391 examine eminence
    **/
    const POSSIBLE_PARAMS = [
        'sport',
        'quel',
        'date',
        'eminence',
        'ids',
        'mars',
    ];
    
    // *****************************************
    /** 
        Routes to the different actions, based on $param
        @param $param Array containing one element (a string)
                      Must be one of self::POSSIBLE_PARAMS
    **/
    public static function execute($params=[]): string{
        $possibleParams_str = implode(', ', self::POSSIBLE_PARAMS);
        if(count($params) != 1){
            return "PARAMETER MISSING in g5\\transform\\newalch\\ertel4391.execute(\$params)\n"
                . "Possible parameters : " . $possibleParams_str;
        }
        $param = $params[0];
        if(!in_array($param, self::POSSIBLE_PARAMS)){
            return "INVALID PARAMETER in g5\\transform\\newalch\\ertel4391.execute(\$params)\n"
                . "Possible parameters : " . $possibleParams_str;
        }
        $method = 'examine_' . $param;
        self::$method();
        return '';
    }
    
    
    // ******************************************************
    /**
        Look at SPORT and IG columns.
    **/
    private static function examine_sport(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $res = []; // assoc array keys = sport codes ; values = [IG, n]
        foreach($rows as $row){
            $sport = $row['SPORT'];
            if(!isset($res[$sport])){
                $res[$sport] = [
                    'IG' => $row['IG'],
                    'n' => 0,
                ];
            }
            $res[$sport]['n'] ++;
            // coherence check
            if($res[$sport]['IG'] != $row['IG']){
                echo "Incoherent association sport / IG, line " . $row['F_NAME'] . ' ' . $row['G_NAME']
                    . ' : ' . $sport . ' ' . $row['IG'] . "\n";
            }
            if(strlen($sport) == 3){
                echo $sport . ' ' . $row['NR'] . ' ' . $row['F_NAME']
                        . ' ' . $row['G_NAME'] . ' ' . $row['IG'] . "\n";
            }
        }
        // print
        ksort($res);
        foreach($res as $sport => $details){
            echo "{$details['IG']} $sport : {$details['n']}\n";
        }
    }
    
    // ******************************************************
    /**
        Look at QUEL column.
    **/
    private static function examine_quel(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $res = []; // assoc codes => nb of records with this code
        foreach($rows as $row){
            if(!isset($res[$row['QUEL']])){
                $res[$row['QUEL']] = 0;
            }
            $res[$row['QUEL']] ++;
        }
        ksort($res);
        echo "\n"; print_r($res); echo "\n";
    }
    
    // ******************************************************
    /**
        Look at DATE column.
    **/
    private static function examine_date(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $N = 0;             // total nb lines
        $nWith = 0;         // nb lines with birth time
        $nWithout = 0;      // nb lines without birth time
        $nWithoutFromG = 0; // nb lines without birth time from Gauquelin LERRCP
        $GCodes = ['*G:D10', 'G:A01', 'G:D06', 'G:D10'];
        foreach($rows as $row){
            $N++;
            $date = $row['DATE'];
            if(strlen($date) == 10){
                $nWithout++;
                if(in_array($row['QUEL'], $GCodes)){
                    $nWithoutFromG++;
                }
            }
            else if(strlen($date) == 16){
                $nWith++;
            }
            else{
                echo 'BUG in date : ' . $row['NR'] . ' ' . $row['F_NAME'] . ' ' . $row['G_NAME'] . ' : ' . $row['DATE'] . "\n";
            }
        }
        // percent
        $pWith = round($nWith * 100 / $N, 2);
        $pWithout = round($nWithout * 100 / $N, 2);
        echo "N total : $N\n";
        echo "N with birth time : $nWith ($pWith %)\n";
        echo "N without birth time : $nWithout ($pWithout %)\n";
        echo "N without birth time from Gauquelin LERRCP : $nWithoutFromG\n";
    }
    
    // ******************************************************
    /**
        Look at eminence columns : ZITRANG ZITSUM ZITATE ZITSUM_OD
    **/
    private static function examine_eminence(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $ranks = []; // assoc array rank => nb records with this rank (ZITRANG)
        $sums = []; // assoc array sums => nb records with this sum (ZITSUM)
        $sources = []; // assoc array sources => nb of records found in this source
        foreach($rows as $row){
            if(!isset($ranks[$row['ZITRANG']])){
                $ranks[$row['ZITRANG']] = 0;
            }
            $ranks[$row['ZITRANG']]++;
            //
            if(!isset($sums[$row['ZITSUM']])){
                $sums[$row['ZITSUM']] = 0;
            }
            $sums[$row['ZITSUM']]++;
            //
            for($i=0; $i < strlen($row['ZITATE']); $i++){
                $char = substr($row['ZITATE'], $i, 1);
                if(!isset($sources[$char])){
                    $sources[$char] = 0;
                }
                $sources[$char]++;
            }
        }
        ksort($ranks);
        ksort($sums);
        ksort($sources);
        echo "\n"; print_r($ranks); echo "\n";
        echo "\n"; print_r($sums); echo "\n";
        echo "\n"; print_r($sources); echo "\n";
        arsort($sources);
        echo "\n"; print_r($sources); echo "\n";
    }
    
    // ******************************************************
    /**
        Look at links to other data sets
        Columns : G_NR PARA_NR CFEPNR CSINR G55
    **/
    private static function examine_ids(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $N = 0;
        $res = [
            'G_NR' => 0,
            'G55' => 0,
            'PARA_NR' => 0,
            'CSINR' => 0,
            'CFEPNR' => 0,
        ];
        foreach($rows as $row){
            $N++;
            if(trim($row['G_NR']) != ''){
                $res['G_NR']++;
            }
            if(trim($row['G55']) != ''){
                $res['G55']++;
            }
            if(trim($row['PARA_NR']) != ''){
                $res['PARA_NR']++;
            }
            if(trim($row['CSINR']) != ''){
                $res['CSINR']++;
            }
            if(trim($row['CFEPNR']) != ''){
                $res['CFEPNR']++;
            }
        }
        $p = []; // percentages
        $p['G_NR'] = round($res['G_NR'] * 100 / $N, 2);
        $p['G55'] = round($res['G55'] * 100 / $N, 2);
        $p['PARA_NR'] = round($res['PARA_NR'] * 100 / $N, 2);
        $p['CSINR'] = round($res['CSINR'] * 100 / $N, 2);
        $p['CFEPNR'] = round($res['CFEPNR'] * 100 / $N, 2);
        //
        $labels = [
            'G_NR' => 'Gauquelin',
            'G55' => 'Gauquelin 1955',
            'PARA_NR' => 'Comité Para',
            'CSINR' => 'CSICOP',
            'CFEPNR' => 'CFEPP',
        ];
        //
        echo "Total : $N\n";
        foreach($res as $k => $v){
            echo "{$labels[$k]} \t $k \t: $v ({$p[$k]} %)\n";
        }
        
        
    }
    
    // ******************************************************
    /**
        Look at mars sectors
        Columns : MARS, MA_, MA12
        Tests if there is a one to one correspondance between the values of the 3 columns
    **/
    private static function examine_mars(){
        $rows = \lib::csvAssociative(Config::$data['dirs']['5-newalch-csv'] . DS . Ertel4391::TMP_CSV_FILE);
        $N = 0;
        $res = [];
        for($i=1; $i <= 36; $i++){
            $res[$i] = [
                'MA_' => [],
                'MA12' => [],
            ];
        }
        foreach($rows as $row){
            if(!in_array($row['MA_'], $res[$row['MARS']]['MA_'])){
                $res[$row['MARS']]['MA_'][] = $row['MA_'];
            }
            if(!in_array($row['MA12'], $res[$row['MARS']]['MA12'])){
                $res[$row['MARS']]['MA12'][] = $row['MA12'];
            }
        }
        $one_to_one = true;
        foreach($res as $s36 => $value){
            if(count($value['MA_']) != 1){
                echo "Problem for sector36, MA_ : $s36\n";
                $one_to_one = false;
            }
            if(count($value['MA12']) != 1){
                echo "Problem for sector36, MA12 : $s36\n";
                $one_to_one = false;
            }
        }
        if(!$one_to_one){
            return;
        }
        echo "<table class=\"wikitable margin\">\n";
        echo "    <tr><th>MARS</th><th>MA_</th><th>MA12</th></tr>\n";
        foreach($res as $s36 => $value){
            echo "    <tr></tr><td>$s36</td><td>{$value['MA_'][0]}</td><td>{$value['MA12'][0]}</td>\n";
        }
        echo "    </tr>\n</table>\n";
    }
    
}// end class
