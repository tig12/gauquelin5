<?php
/********************************************************************************
    Importation of 1-irving/rawlins-ertel-data.csv
    to  5-csicop/408-csicop-irving.csv
    
    @license    GPL
    @history    2019-12-23 00:38:49+01:00, Thierry Graff : Creation
********************************************************************************/
namespace g5\transform\csicop\irving;

use g5\G5;
use g5\Config;
use g5\patterns\Command;

class raw2csv implements Command{

    // *****************************************
    /** 
        @param  $params Empty array
        @return String report                                                                 
    **/
    public static function execute($params=[]): string {
        if(count($params) > 0){
            return "USELESS PARAMETER : " . $params[0] . "\n";
        }
        
        $infile = Irving::raw_filename();
        $outfile = Irving::tmp_filename();
        
        $report =  "--- Importing file $infile ---\n";
        
        $rows = file($infile);
        
        $n = 0;
        $res = [];
        for($i=0; $i < count($rows); $i++){
            if($i == 0){
                continue; // line containing field names
            }
            $fields = explode(Irving::RAW_CSV_SEP, $rows[$i]);
            $new = [];
            // HERE modify some ids to conform to si42
            $id = $fields[0];
            if(isset(Irving::IRVING_SI42[$id])){
                $new['CSID'] = Irving::IRVING_SI42[$id];
            }
            else{
                $new['CSID'] = $id;
            }
            $new['FNAME'] = $fields[1];
            $new['GNAME'] = $fields[2];
            $day = $fields[5]
                 . '-' . str_pad($fields[4] , 2, '0', STR_PAD_LEFT)
                 . '-' . str_pad($fields[3] , 2, '0', STR_PAD_LEFT);
            $h = $fields[6];
            if($fields[8] == 'P' || $fields[8] == 'P1'){
                // consider P1 because 2 records are bugged
                $h += 12;
            }
            $h = str_pad($h , 2, '0', STR_PAD_LEFT);
            $min = str_pad($fields[7] , 2, '0', STR_PAD_LEFT);
            $new['DATE'] = "$day $h:$min";
            $new['TZ'] = self::tz($fields[9]);
            $new['LG'] = -self::lgLat($fields[11], $fields[12]);
            $new['LAT'] = self::lgLat($fields[13], $fields[14]);
            $new['C2'] = $fields[10];
            $new['CY'] = 'US';
            $new['SPORT'] = $fields[15];
            $new['MA36'] = $fields[16];
            $new['CANVAS'] = trim($fields[17]);
            $res[$new['CSID']] = $new; // key here only useful for ksort
            $n++;
        }
        
        ksort($res); // necessary because of inversions generated by Irving::IRVING_SI42
        
        $output = implode(G5::CSV_SEP, Irving::TMP_FIELDS) . "\n";
        foreach($res as $row){
            $output .= implode(G5::CSV_SEP, $row) . "\n";
        }
        file_put_contents($outfile, $output);
        $report .= "Generated $outfile - $n records stored\n";
        return $report;
    }
    
    // ******************************************************
    /**
        Auxiliary of execute()
    **/
    private static function lgLat($deg, $min){
        return $deg + round(($min / 60), 5);
    }
    
    // ******************************************************
    /**
        Auxiliary of execute()
        Computes the timezone offset
    **/
    private static function tz($str){
        if($str == '0,5'){
            // bug for 2 records
            return '-10:30';
        }
        // all other records contain integer offsets
        return '-' . str_pad($str , 2, '0', STR_PAD_LEFT) . ':00';
    }
    
}// end class