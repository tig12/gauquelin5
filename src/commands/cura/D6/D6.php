<?php
/********************************************************************************
    Contains code common to several D6 classes.
    
    @license    GPL
    @history    2019-06-07, Thierry Graff : Creation
********************************************************************************/
namespace g5\commands\cura\D6;

class D6{
    
    // TRUST_LEVEL not defined, using value of class Cura
    
    /** Fields of data/raw/cura/D6.csv **/
    const RAW_FIELDS = [
        'NUM',
        'DAY',
        'MON',
        'YEA',
        'H',
        'MN',
        'SEC',
        'LAT',
        'LON',
        'NAME',
    ];

    /** Fields of data/tmp/cura/D6.csv **/
    const TMP_FIELDS = [
        'NUM',
        'FNAME',
        'GNAME',
        'OCCU',
        'DATE',
        'PLACE',
        'CY',
        'C2',
        'GEOID',
        'LG',
        'LAT',
    ];
    
}// end class    
