<?php
/******************************************************************************
    Computation of timezone offset for France.
    - Takes into account the fact that Alsace and a part of Lorraine were german between 1870 and 1918
    - Takes into account the fact that before 1891-03-15, local hour was used
    BUT NOT EXACT :
    - some part of dept 54 was also german between 1871 and 1918
        => a precise computation should take into account the precise local situation
    - Some parts of depts 06, 04, 26, 74, 73 were not french before 1860
    - Between 1940-02 and 1942-11, France was divided in 2 zones (occupied and free)
        => a precise computation should take into account the precise local situation
    Computations are based on "Traité de l'heure dans le monde", 5th edition, 1990

    @license    GPL
    @history    2017-01-03 00:09:55+01:00, Thierry Graff : Creation 
    @history    2017-05-04 10:38:22+02:00, Thierry Graff : Adaptation for autonom use 
    @history    2019-06-11 10:41:23+02:00, Thierry Graff : Integration to tiglib
********************************************************************************/
namespace tiglib\timezone;

use tiglib\time\seconds2HHMMSS;
use soniakeys\meeus\eqtime\eqtime;

class offset_fr{
    
    // return codes and messages
    const CASE_1871_1918_LORRAINE = 1;
    const MSG_1871_1918_LORRAINE = 'Possible TZ offset error (german zone 1871 - 1918 ; dept 54, 57, 88)';
    
    const CASE_1871_1918_ALSACE = 2;
    const MSG_1871_1918_ALSACE = 'Possible TZ offset error (german zone 1871 - 1918 ; dept 67, 68)';
    
    const CASE_WW2 = 3;
    const MSG_WW2 = 'Possible timezone offset error (german occupation WW2)';
    
    const CASE_BEFORE_1891 = 4;
    
    const CASE_PHP_DEFAULT = 5;
    
    // ******************************************************
    /**
        Computation of timezone offset for France.
        @param  $date   ISO 8601 HH:MM or HH:MM:SS
        @param  $lg     longitude in decimal degrees
        @param  $c2     Département ("75" for Paris, "01" for Ain etc.)
        @param  $format Format of the returned offset - Can be 'HH:MM' or 'HH:MM:SS'
        
        @return array with 3 elements : 
                - the timezone offset, format sHH:MM (ex : '-01:00' ; '+00:23') 
                  or empty string if unable to compute.
                - an error message ; empty string if offset could be computed without ambiguity.
                - an integer indicating the kind of computation involved (see code, variable $case).
                
        @todo   Implementation of computation taking into account the precise local situations
                would need also a latitude parameter
        @todo   Consider equation of time for dates < 1891-03-15
    **/
    public static function compute($date, $lg, $c2, $format='HH:MM'){
        if($format != 'HH:MM' && $format != 'HH:MM:SS'){
            throw new Exception("Invalid \$format parameter : $format - Must be 'HH:MM' or 'HH:MM:SS'");
        }
        $err = $offset = '';
        $case = 0;
        
        if($date > '1871-02-15' && $date < '1918-11-11'){
            // why 1871-02-15 and not 1871-05-10 ?
            /* 
            http://abreschviller.fr/LA-GUERRE-FRANC-PRUSSIENNE-ET-L-ANNEXION
            Signé le 10 Mai 1871 à Francfort, le traité de Paix enlevait à la France
            67, 68 : Alsace,
            57 : Moselle (l’ensemble du département, exception faite de l’arrondissement de Briey),
            54 et 57 : un tiers de la Meurthe (les arrondissements de Sarrebourg et Château-Salins)
            88 : Vosges (la vallée de la Bruche, de Schirmeck à Saales).             
            */
            if(in_array($c2, [54, 57, 88])){
                $case = self::CASE_1871_1918_LORRAINE;
                $err = self::MSG_1871_1918_LORRAINE . " - dept $c2 - $date";
            }
            else if(in_array($c2, [67, 68])){
                $case = self::CASE_1871_1918_ALSACE;
                //$zone = 'Europe/Berlin';
                // @todo Possible to compute
                $err = self::MSG_1871_1918_ALSACE . " - dept $c2 - $date";
            }
        }                                                         
        if($date >= '1940-02' && $date <= '1942-11'){
            $case = self::CASE_WW2;
            $err = self::MSG_WW2 . " : $date";
        }
        
        if($err != ''){
            return [$offset, $err, $case];
        }
        
        if($date < '1891-03-15'){
            $case = self::CASE_BEFORE_1891;
            // From "Traité de l'heure dans le monde" :
            // legal hour HL = HLO, local hour at real sun
            // and UT = HLO - Lg - E (E = equation of time)
            // offset = HL - UT
            //        = HLO - (HLO - Lg - E)
            //        = Lg + E
            $lg_seconds = 240 * $lg; // 240 = 24 * 3600 / 360 = nb of time seconds per longitude degree
            $eqtime_seconds = eqtime::compute(substr($date, 0, 10));
            $offset_seconds = $lg_seconds + $eqtime_seconds;
            $hhmmss = $format == 'HH:MM' ? seconds2HHMMSS::compute($offset_seconds, true) : seconds2HHMMSS::compute($offset_seconds);
            $sign = ($offset_seconds < 0 && $hhmmss != '00:00') ? '-' : '+';
            $offset = $sign . $hhmmss;
        }
        else{
            $case = self::CASE_PHP_DEFAULT;
            $offset = offset::compute($date, 'Europe/Paris', $format);
        }
        
        return [$offset, $err, $case];
    }

    
}// end class
