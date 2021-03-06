<?php
/********************************************************************************
    Builds a list of 458 mathematicians ranked by eminence.
    Data source : book
        Éléments d'histoire des Mathématiques
        by Nicolas Bourbaki
        Ed. Springer
        2007 (3rd edition ; reprint from 1984 version, ed. Masson)
        Uses the "INDEX DES NOMS CITÉS", pp 366 - 376 of the book
    
    @license    GPL
    @history    2020-11-26 21:59:17+01:00, Thierry Graff : Creation
********************************************************************************/
namespace g5\commands\eminence\math;

use g5\G5;
use g5\Config;
use g5\patterns\Command;
use g5\model\{Source, SourceI, Group};
use tiglib\arrays\sortByKey;

// *****************************************
//          Model class
// *****************************************
class BourbakiModel implements SourceI {

    // TRUST_LEVEL not defined, using value of class Newalch
    
    /**
        Path to the yaml file containing the characteristics of the source.
        Relative to directory data/model/source
    **/
    const SOURCE_DEFINITION = 'eminence' . DS . 'math' . DS . 'bourbaki.yml';

    /** Slug of the group in db **/
    const GROUP_SLUG = 'peiffer-dahan-dalmenico';
    
    // *********************** Source management ***********************
    
    /** @return a Source object for the raw file. **/
    public static function getSource(): Source {
        return Source::getSource(Config::$data['dirs']['model'] . DS . self::SOURCE_DEFINITION);
    }
    
    // *********************** Raw files manipulation ***********************
    
    /** @return Path to the raw file **/
    public static function rawFilename(){
        return implode(DS, [Config::$data['dirs']['raw'], 'eminence', 'math', 'bourbaki.txt']);
    }
    
    /** Loads raw file in a regular array **/
    public static function loadRawFile(){
        return file(self::rawFilename(), FILE_IGNORE_NEW_LINES);
    }                                                                                              
    
    // *********************** Tmp file manipulation ***********************
    
    /** @return Path to the csv file stored in data/tmp/ **/
    public static function tmpFilename(){
        return implode(DS, [Config::$data['dirs']['tmp'], 'eminence', 'math', 'bourbaki.csv']);
    }
    
}


// *****************************************
//          Implementation of Command
// *****************************************
class bourbaki implements Command {
    
    /**  Possible values of the command **/
    const POSSIBLE_PARAMS = [
        'raw2tmp',
    ];
    
    /** 
        @param  $params
                    - $params[0] contains the name of the action (ex raw2tmp)
                    - Other params are passed to the exec_* method
        @return String report
    **/
    public static function execute($params=[]): string{
        $possibleParams_str = implode(', ', self::POSSIBLE_PARAMS);
        if(count($params) == 0){
            return "PARAMETER MISSING\n"
                . "Possible values for parameter : $possibleParams_str\n";
        }
        $param = $params[0];
        if(!in_array($param, self::POSSIBLE_PARAMS)){
            return "INVALID PARAMETER\n"
                . "Possible values for parameter : $possibleParams_str\n";
        }
        
        $method = 'exec_' . $param;
        
        if(count($params) > 1){
            array_shift($params);
            return self::$method($params);
        }
        
        return self::$method();
    }
    
    // ******************************************************
    /**
        Input
            data/raw/eminence/maths/bourbaki.txt
        Output
            data/tmp/eminence/math/bourbaki.csv
    **/
    private static function exec_raw2tmp(){
        $report =  "--- bourbaki raw2tmp ---\n";
        $lines = BourbakiModel::loadRawFile();
        $N = 0;
        $p = '/(.*?),\s*(\d.*)/';
        $res = [];
        foreach($lines as $line){
            $line = trim($line);
            if($line == ''){
                continue;
            }
            $N++;
            if(!preg_match($p, $line, $m)){
                echo "NO MATCH === $line\n";
            }
            $pages = self::computePages($m[2]);
            $m[1] = str_replace(' 77', '', $m[1]); // fix error of input file : "Van der Waerden 77"
            // family given names
            $fname = trim($m[1]);
            $gname = '';
            $pos = strpos($m[1], '(');
            if($pos){
                $fname = substr($fname, 0, $pos);
                $gname = substr($m[1], $pos+1, -1);
            }
            $res[] = [
                'FNAME' => $fname,
                'GNAME' => $gname,
                'SCORE' => count($pages),
                'PAGES' => $pages,
            ];
        }
        $res = sortByKey::compute($res, 'SCORE');
        //
        $res2 = implode(G5::CSV_SEP, ['FNAME', 'GNAME', 'SCORE', 'PAGES']) . "\n";
        for($i=count($res)-1; $i >= 0; $i--){
            $cur = $res[$i];
            $cur['PAGES'] = implode('+', $cur['PAGES']);
            $res2 .= implode(G5::CSV_SEP, $cur) . "\n";
        }
        //
        $outfile = BourbakiModel::tmpFilename();
        file_put_contents($outfile, $res2);
        $report .= "Wrote $N records in $outfile\n";
        return $report;
    }
    
    /**
        Auxiliary of exec_raw2tmp()
        @param  $str    Examples :
                        117, 225, 232, 271, 274
                         117, 118, 120 à 124, 127, 128
        @return Array of page numbers
    **/
    public static function computePages($str){
        $parts = explode(',', $str);
        $res = [];
        foreach($parts as $part){
            $part = trim($part);
            $part = str_replace('.', '', $part);
            if(is_numeric($part)){
                // single page number
                // cast to solve bug (a dot sometimes remains ar the end) - not understood
                $res[] = (int)$part;
                continue;
            }
            // page range, like 174-176
            $tmp = explode('à', $part);
            if(count($tmp) != 2){
                echo "ERROR in computePages($str) : $part\n";
                continue;
            }
            [$p1, $p2] = $tmp;
            // cast to solve bug (a dot sometimes remains ar the end) - not understood
            $p1 = (int)$p1;
            $p2 = (int)$p2;
            for($p=$p1; $p <= $p2; $p++){
                $res[] = $p;
            }
        }
        return $res;
    }
    
}// end class
