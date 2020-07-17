<?php
/******************************************************************************
    Gauquelin5 database
    @license    GPL
    @history    2019-12-27 05:50:58+01:00, Thierry Graff : Creation
********************************************************************************/
namespace g5\model;

use g5\Config;
use tiglib\strings\slugify;

DB5::init();

class DB5{
    
    // Trust levels, see https://tig12.github.io/gauquelin5/check.html
    const TRUST_HC = 1;                                                                  
    const TRUST_BC = 2;
    const TRUST_BC_CHECK = 2.5;
    const TRUST_BR = 3;
    const TRUST_CHECK = 4;
    const TRUST_REST = 5;
    
    /** Separator used to build uids **/
    const SEP = '/';
    
    /** Path pointing to 7-full **/             public static $DIR; 
    /** Path pointing to 7-full/tmp/index **/   public static $DIR_INDEX; 
    /** Path pointing to 7-full/group **/       public static $DIR_GROUP;
    /** Path pointing to 7-full/person **/      public static $DIR_PERSON;
    /** Path pointing to 7-full/source **/      public static $DIR_SOURCE;
    
    /**
        Pattern to check a date.
        @todo put elsewhere ?
    **/
    const PDATE = '/\d{4}-\d{2}-\d{2}/';
    
    // ******************************************************
    public static function init(){
        self::$DIR = Config::$data['dirs']['7-full'];
        self::$DIR_INDEX = self::$DIR . DS . 'tmp' . DS . 'index';
        self::$DIR_GROUP = self::$DIR . DS . 'group';
        self::$DIR_PERSON = self::$DIR . DS . 'person';
        self::$DIR_SOURCE = self::$DIR . DS . 'source';
    }
    
}// end class
