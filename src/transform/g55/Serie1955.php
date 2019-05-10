<?php
/********************************************************************************
    Generation of the groups published in Gauquelin's book in 1955
    Input data are the files contained in 3-g55-edited
    Outputs files in two directories : 9-g55-original and 9-1955-corrected
    
    @license    GPL
    @history    2017-05-08 23:39:19+02:00, Thierry Graff : creation
    @history    2019-04-08 15:24:04+02:00, Thierry Graff : Start generation of 2 versions : original and corrected
********************************************************************************/
namespace g5\transform\g55;

use g5\init\Config;

class Serie1955{
    
    // grrrr, libreoffice transformed ; in ,
    // didn't find this fucking setting
    const CSV_SEP_LIBREOFFICE = ',';
    
    /**
        1955 groups ; format : group code => [name, serie]
        serie is 'NONE' for groups that can't be found in cura data
    **/
    const GROUPS_1955 = [
        '576MED' => ["576 membres associés et correspondants de l'académie de médecine", 'A2'],
        '508MED' => ['508 autres médecins notables', 'A2'],
        '570SPO' => ['570 sportifs', 'A1'],
        '676MIL' => ['676 militaires', 'A3'],
        '906PEI' => ['906 peintres', 'A4'],
        '361PEI' => ['361 peintres mineurs', 'NONE'],
        '500ACT' => ['500 acteurs', 'A5'],
        '494DEP' => ['494 députés', 'A5'],
        '349SCI' => ["349 membres, associés et correspondants de l'académie des sciences", 'A2'],
        '884PRE' => ['884 prêtres', 'NONE'],
    ];

    /**  Matching between place names used in the edited files and geonames.org corresponding names **/
    const GEONAMES_PLACES = [
        'Alger' => 'Algiers',
    ];
    
    // *****************************************
    /** 
        Generates the csv files in 9-g55-original/
        Uses files located in 3-g55-edited/ and files located in 5-cura-csv/
        
        Called by : php run-gauquelin5.php 1955 finalize
        
        @param  $serie  String must be '1955' - useless but kept for conformity with other classes
        @return report
        @throws Exception if unable to parse
    **/
    public static function generateCorrected($serie){
        die("\n<br>die here " . __FILE__ . ' - line ' . __LINE__ . "\n");
    }
        
        
    // *****************************************
    /** 
        Generates the csv files in 9-g55-original/ from csv files located in 3-g55-edited/
        See 9-g55-original/README for a meaning of generated fields
        
        Called by : php run-gauquelin5.php 1955 finalize
        
        @param  $serie  String must be '1955' - useless but kept for conformity with other classes
        @return report
        @throws Exception if unable to parse
    **/
    public static function generateOriginal($serie){
        $src_dir = Config::$data['dirs']['3-g55-edited'];
        $dest_dir = Config::$data['dirs']['9-g55-original'];
        $files = glob($src_dir . DS . '*.csv');
        $generatedFields = [
            'ORIGIN' => '',
            'NUM' => '',
            'FAMILYNAME' => '',
            'GIVENNAME' => '',
            'PRO' => '',
            'BIRTHDATE' => '',
            'BIRTHPLACE' => '',
            'COU' => '',
            'ADM2' => '',
            'GEOID' => '',
            'LG' => '',
            'LAT' => '',
//            'COMMENT' => '',
        ];
        $firstline = implode(Config::$data['CSV_SEP'], array_keys($generatedFields));
        foreach($files as $file){
            $res = $firstline . "\n";
            $groupCode = str_replace('.csv', '', basename($file));
            $lines = file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
            $fieldnames = explode(self::CSV_SEP_LIBREOFFICE, $lines[0]);
            array_shift($lines); // line containing field names
            foreach($lines as $line){
                $new = $generatedFields;
                // turn $fields to be [ 'ORIGIN' => 'A1', 'NUM' => '6', 'NAME' => 'Bally Etienne', ...]
// @todo externalize this code
                $tmp = explode(self::CSV_SEP_LIBREOFFICE, $line);
                foreach($fieldnames as $k => $v){
                    $fields[$v] = $tmp[$k];
                }
// end @todo externalize this code
                //
                $new['ORIGIN'] = $fields['ORIGIN'];
                $new['NUM'] = $fields['NUM'];
                // name
                $tmp = explode(' ', $fields['NAME']);
                if(count($tmp) != 2){
                    // can happen for 2 cases :
                    // - persons not in cura files and added by a human
                    // - persons with composed last names
                    // In both cases, GIVEN_C and FAMILY_C should be filled
                    if($fields['GIVEN_C'] == '' || $fields['FAMILY_C'] == ''){
                        echo "ANOMALY ON NAMES - {$new['NUM']} - LINE SKIPPED, MUST BE FIXED\n";
                        continue;
                    }
                    else{
                        $family = $fields['FAMILY_C'];
                        $given = $fields['GIVEN_C'];
                    }
                }
                else{
                    [$family, $given] = explode(' ', $fields['NAME']); // ex 'Bally Etienne'
                    // If GIVEN_C or FAMILY_C are filled, override cura value
                    if($fields['FAMILY_C'] != ''){
                        $family = $fields['FAMILY_C'];
                    }
                    if($fields['GIVEN_C'] != ''){
                        $given = $fields['GIVEN_C'];
                    }
                }
                $new['FAMILYNAME'] = $family;
                $new['GIVENNAME'] = $given;
                // profession
                if($fields['PRO_C'] != ''){
                    $new['PRO'] = $fields['PRO_C'];
                }
                else{
                    $new['PRO'] = $fields['PRO'];
                }
                //
                // place
                // processed before date to find timezone from geonames
                // but date is put before place in resulting line
                if($fields['PLACE_C'] != ''){
                    $place = $fields['PLACE_C'];
                }
                else{
                    $place = $fields['PLACE'];
                }
                if($fields['COU_C'] != ''){
                    $country = $fields['COU_C'];
                }
                else{
                    $country = $fields['COU'];
                }
                if($fields['COD_C'] != ''){
                    $admin2 = $fields['COD_C'];
                }
                else{
                    $admin2 = $fields['COD'];
                }
                if($admin2 == 'NONE'){
                    $admin2 = '';
                }
                if(strlen($admin2) == 1 && $country == 'FR'){
                    $admin2 = '0' . $admin2; // because libreoffice "eats" the trailing 0
                }
                // HERE try to match Geonames
                $slug = \lib::slugify($place);
                $geonames = \Geonames::matchFromSlug([
                    'slug' => $slug,
                    'countries' => [$country],
                    'admin2-code' => $admin2,
                ]);
                if(!$geonames){
                    echo "ERROR: COULD NOT MATCH GEONAMES - {$new['NUM']} - $slug $admin2 - LINE SKIPPED, MUST BE FIXED\n";
                    continue;
                }
                $new['BIRTHPLACE']  = $geonames[0]['name'];
                $new['GEOID']       = $geonames[0]['geoid'];
                $new['LG']          = $geonames[0]['longitude'];
                $new['LAT']         = $geonames[0]['latitude'];
                $new['ADM2']        = $admin2;
                $new['COU']         = $country;
                //
                // birth date
                //
                try{
                    [$day, $hour] = self::computeBirthDate($fields['DATE'], $fields['DAY_C'], $fields['HOUR_C']);
                }
                catch(\Exception $e){
                    echo "ERROR: COULD NOT COMPUTE BIRTHDATE - {$new['NUM']} - $slug - LINE SKIPPED, MUST BE FIXED\n";
                    echo $e->getMessage() . "\n";
                    continue;
                }
                // dtu
                $dtu = '';
                if($country == 'FR'){
                    [$dtu, $err] = \TZ_fr::offset("$day $hour", $new['LG'], $new['ADM2']);
                    if($err != ''){
                        // err is something like :
                        // Possible timezone offset error (dept 54) - check precise local conditions
                        echo "ERROR for {$new['NUM']} {$new['FAMILYNAME']} {$new['GIVENNAME']} : " . $err . " - LINE SKIPPED, MUST BE FIXED\n";
                        continue;
                    }
                }
                else{
                    $dtu = \TZ::offset("$day $hour", $geonames[0]['timezone']);
                }
                $new['BIRTHDATE'] = "$day $hour$dtu";
                // add new line to res
                $res .= implode(Config::$data['CSV_SEP'], $new) . "\n";
            }
            // write output
            $dest_file = $dest_dir . DS . $groupCode . '.csv';
            file_put_contents($dest_file, $res);
            echo "$dest_file generated\n";
        }
    }
    
    
    // ******************************************************
    /**
        Auxiliary of self::generateCorrected()
        @param $date_cura   content of column DATE
        @param $day_c       content of column DAY_C
        @param $hour_c      content of column HOUR_C
        @return Array containing day and hour in format [YYYY-MM-DD, HH:MM:SS]
        @throws Exception
                    - in case of incoherence between cura and corrected data
                    - in case of incomplete data
    **/
    private static function computeBirthDate($date_cura, $day_c, $hour_c){
        if($day_c == '' && $hour_c == ''){
            // no checks on $date_cura, supposed correct
            $day = substr($date_cura, 0, 10);
            $hour = substr($date_cura, 11, 8);
            return [$day, $hour];
        }
        if($date_cura == ''){
            // happens for some rows, present in Gauquelin book, and not in cura data (ex Jacques Lunnis)
            if($day_c == ''){
                throw new \Exception("Missing column DAY_C");
            }                                                                                                         
            if($hour_c == ''){
                throw new \Exception("Missing column HOUR_C");
            }
        }
        if(is_numeric($hour_c)){
            // ex : convert '14' to '14:00:00'
            // obliged to do that because of libre office automatic conversion
            $hour_c = str_pad($hour_c, 2, '0', STR_PAD_LEFT) . ':00:00';
        }
        //
        $day = substr($date_cura, 0, 10);
        $hour = substr($date_cura, 11, 8);
        // override with corrected values
        if($day_c != ''){
            $day = $day_c;
        }
        if($hour_c != ''){
            $hour = $hour_c;
        }
        return [$day, $hour];
    }
    
    
    // *****************************************
    /** 
        Generates the 1955 files in 5-g55-generated/ from :
        - csv files located in 5-cura-csv/
        - csv files located in 3-cura-marked/
        Takes an exact copy of files in 5-cura-csv
        Uses files from 3-cura-marked to filter and dispatch in different resulting files
        Adds a column ORIGIN
        
        Called by : php run-gauquelin5.php 1955 marked2generated
        
        @param  $serie  String must be '1955' - useless but kept for conformity with other classes
        @return report
        @throws Exception if unable to parse
    **/
    public static function marked2generated($serie){
        $src_dir = Config::$data['dirs']['3-cura-marked'];
        $dest_dir = Config::$data['dirs']['5-g55-generated'];
        
        $groups55 = self::loadGroups3($src_dir);
        
        foreach(self::GROUPS_1955 as $groupCode => [$groupName, $serie]){
            if(count($groups55[$groupCode]) == 0){
                continue; // for groups not treated yet
            }
            echo "Generating 1955 group $groupCode : $groupName\n";
            $res = [];
            $inputfile = Config::$data['dirs']['5-cura-csv'] . DS . $serie . '.csv'; // file generated by class SerieA
            $input = file($inputfile);
            $N = count($input);
            $fieldnames = explode(Config::$data['CSV_SEP'], $input[0]);
            for($i=1; $i < $N; $i++){
                $fields = explode(Config::$data['CSV_SEP'], $input[$i]);
                $NUM = $fields[0]; // by convention, all generated csv file of 5-cura-csv have NUM as first field
                if(!in_array($NUM, $groups55[$groupCode])){
                    continue;
                }
                $res[] = $fields;
            }
            //
            // sort $res
            //
            // here simplification : files 5-cura-csv/A1.csv and A2.csv
            // have first field = NUM and second field = NAME
            $sort_field = (Config::$data['1955']['sort'][$groupCode] == 'NUM' ? 0 : 1);
            $res = \lib::sortByKey($res, $sort_field);
            echo '  ' . count($res) . " persons stored\n";
            // generate output
            $output = 'ORIGIN' . Config::$data['CSV_SEP'] . $input[0]; // field names
            foreach($res as $fields){
                $output .= $serie . Config::$data['CSV_SEP'] . implode(Config::$data['CSV_SEP'], $fields);
            }
            $dest_file = $dest_dir . DS . $groupCode . '.csv';
            file_put_contents($dest_file, $output);
        }
    }
    
    
    // ******************************************************
    /**
        Loads the csv files located in 3-cura-marked/ in arrays
        Auxiliary of self::marked2generated()
        @param $src_dir String Directory called 3-cura-marked/ in config
        @return associative array :
                group code => array containing the values of NUM in this group
                group codes are keys of self::GROUPS_1955
    **/
    private static function loadGroups3($src_dir){
        $res = [];
        foreach(self::GROUPS_1955 as $groupCode => [$name, $serie]){
            $res[$groupCode] = [];
            $raw = @file_get_contents($src_dir . DS . $serie . '.csv');
            if($raw === false){
                continue;
            }
            $raw = str_replace('"', '', $raw); // libreoffice adds " and I don't know how to remove them
            $lines = explode("\n", $raw);
            $nlines = count($lines);
            $fieldnames = explode(self::CSV_SEP_LIBREOFFICE, $lines[0]);
            $flip = array_flip($fieldnames);
            for($i=1; $i < $nlines; $i++){
                if(trim($lines[$i]) == ''){
                    continue;
                }
                $fields = explode(self::CSV_SEP_LIBREOFFICE, $lines[$i]);
                $code = $fields[$flip['1955']];
                if($code != $groupCode){
                    continue;
                }
                $res[$groupCode][] = $fields[$flip['NUM']];
            }
        }
        return $res;
    }
    
}// end class    

