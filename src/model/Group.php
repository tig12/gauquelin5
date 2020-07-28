<?php
/******************************************************************************
    Regular array containing person paths (strings)
    ex of persons/1864/12/16/machin-pierre
    
    @license    GPL
    @history    2019-12-27 23:20:16+01:00, Thierry Graff : Creation
********************************************************************************/

namespace g5\model;

use g5\Config;                           
use g5\G5;

class Group{
    
    public $data = [];
    
    // *********************** new *******************************
    /**
        Create an object of type Group from its uid.
        @param $uid     String like group/web/cura/A1
    **/
    public static function new($uid){
        $g = new Group();
        $g->data['uid'] = $uid;
        $g->load();
        return $g;
    }
    
    /** Returns an empty object of type Group. **/
    public static function newEmpty($uid=''){
        $g = new Group();
        $g->data = yaml_parse(file_get_contents(__DIR__ . DS . 'Group.yml'));
        if($uid != ''){
            $g->data['uid'] = $uid;
        }
        return $g;
    }
    
    // ************************ id ******************************

    public function uid(){
        return $this->data['uid'];
    }
    
    public function slug(): string {
        $tmp = explode(DB5::SEP, $this->uid());
        return $tmp[count($tmp)-1];
    }
    
    // *********************** fields *******************************
    
    public function add($entry){
        $this->data['members'][] = $entry;
    }
    
    // *********************** file system *******************************
    
    public function file($full=true): string {
        $res = $full ? DB5::$DIR . DB5::SEP : '';
        $res .= 'tmp/'; // WARNING HACK
        $res .= $this->uid() . '.txt';
        return str_replace(DB5::SEP, DS, $res);
    }
    
    /**
        Read from disk
        txt files in 7-full/tmp/group
    **/
    public function load(){
        $path = $this->file();
        if(!is_file($path)){
            throw new \Exception(
                "IMPOSSIBLE TO LOAD GROUP - file does not exist: $path\n"
            );
        }
        $this->data['members'] = file($path,  FILE_IGNORE_NEW_LINES);
    }
    
    /** 
        Write on disk
        => Text file with one person slug per line 
    **/
    public function save(){
        $path = $this->file();
        $dir = dirname($path);
        if(!is_dir($dir)){
            mkdir($dir, 0755, true);
        }
        $dump = '';
        // not sorted, keep the order decided by client code 
        foreach($this->data['members'] as $elt){
            $dump .= $elt . "\n";
        }
        file_put_contents($path, $dump);
        // echo "___ file_put_contents $path\n"; // @todo log
    }
    
    /** 
        Generates a csv from its members
            first line contains field names
            other lines contain data
        @param $csvFile 
        @param $csvFields
            Names of the fields of the generated csv
            Are written in this order in the csv
            $csvFields = ['GID', 'FNAME', 'GNAME', 'OCCU', '...', 'GEOID']
        @param $map
            $map = [
                'ids.cura' => 'GID',
                'fname' => 'FNAME',
                'gname' => 'GNAME',
                // ...
                'birth.place.geoid' => 'GEOID',
            ];
        
        @param $fmap Assoc array
                    key = field name in generated csv
                    value = function computing this field's value to write in the csv
                             parameter : a person
                             return : the value of the csv field
                    $fmap = [
                        'OCCU' => function($p){
                            return implode('+', $p->data['occus']);
                        },
                    ];
        @param $filters Regular array of functions returning a boolean
                        If one of these functions returns false on a group member,
                        export() skips the record.
        
    **/
    public function exportCsv($csvFile, $csvFields, $map=[], $fmap=[], $filters=[]){
        
        $csv = implode(G5::CSV_SEP, $csvFields) . "\n";
        
        $emptyNew = array_fill_keys($csvFields, '');
        
        foreach($this->data['members'] as $puid){
            $p = Person::new($puid);
            // filters
            foreach($filters as $function){
                //echo $p->slug() . "\n";
                //echo ($function($p) ? 'true' : 'false') . "\n";
                if(!$function($p)){
                    continue 2;
                }
            }
            $new = $emptyNew;
            // map
            foreach($map as $personKey => $csvKey){
                $pks = explode('.', $personKey); // @todo put '.' in a constant
                $data = null;
                foreach($pks as $pk){
                    if(!isset($p->data[$pk])){
                        // means an incoherence of data
                    }
                    $data = is_null($data) ? $p->data[$pk] : $data[$pk];
                }
                $new[$csvKey] = $data;
            }
            // fmap
            foreach($fmap as $csvKey => $function){
                $new[$csvKey] = $function($p);
            }
            $csv .= implode(G5::CSV_SEP, $new) . "\n";
        }
        
        file_put_contents($csvFile, $csv);
        // echo "___ file_put_contents $csvFile\n"; // @todo 
    }
    
}// end class
