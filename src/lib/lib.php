<?php
/********************************************************************************
    Miscelaneous generic functions
    
    @license    GPL
    @author     Thierry Graff
    @history    2007-10-25 21:58, Thierry Graff : Creation
********************************************************************************/
class lib{
    
    /** Auxiliary variable of sortByKey(), for usort(). */
    private static $sortByKey_keyname;
    
    
    //***************************************************
    /**
        Sorts a 2 dim array, using one of the key of its elements to sort.
        Param $array must be a regular array composed of associative arrays
        Each of these associative array must have a key named by $keyname
        Ex : 
        $array = [
            0=>['name'=>'toto', 'age'=>45],
            1=>['name'=>'titi', 'age'=>25],
            2=>['name'=>'tata', 'age'=>35]
        ];
        $array2 = jth_sortByKey::sortByKey($array, 'name');
        // then we have :
        $array2 = [
            0=>['name'=>'tata', 'age'=>35],
            1=>['name'=>'titi', 'age'=>25],
            2=>['name'=>'toto', 'age'=>45]
        ];
        $array3 = jth_sortByKey::sortByKey($array, 'age');
        // then we have :
        $array3 = [
            1=>['name'=>'titi', 'age'=>25],
            0=>['name'=>'tata', 'age'=>35],
            2=>['name'=>'toto', 'age'=>45]
        ];
        @param      $array Array to sort
        @param      $keyname Name of the key used to sort
        @return     The sorted array
    **/
    public static function sortByKey($array, $keyname){
        self::$sortByKey_keyname = $keyname;
        usort($array, ['lib', 'sortByKey_aux']);
        return $array;
    }
    
    
    //***************************************************
    /** Auxiliary function of sortByKey(), for usort(). **/
    private static function sortByKey_aux($a, $b){
        if ($a[self::$sortByKey_keyname] == $b[self::$sortByKey_keyname]) return 0;
        return $a[self::$sortByKey_keyname] < $b[self::$sortByKey_keyname] ? -1 : 1;
    }
    
    
}// end class
