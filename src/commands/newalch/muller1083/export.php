<?php
/********************************************************************************
    Generates data/output/history/1994-muller-medics/muller-1083-medics.csv
    
    @license    GPL
    @history    2020-09-12 17:27:59+02:00, Thierry Graff : creation
********************************************************************************/
namespace g5\commands\newalch\muller1083;

use g5\Config;
use g5\model\DB5;
use g5\model\Group;
use g5\model\Names_fr;
use g5\patterns\Command;
use g5\commands\newalch\Newalch;
use g5\commands\cura\Cura;

class export implements Command {
                                                                                              
    /**
        Directory where the generated files are stored
        Relative to directory specified in config.yml by dirs / output
    **/
    const OUTPUT_DIR = 'history' . DS . '1994-muller-physicians';
    
    const OUTPUT_FILE = 'muller-1083-physicians.csv';
    
    /**  Trick to access to $sourceSlug within $sort function **/
    private static $sourceSlug;
    
    // *****************************************
    // Implementation of Command
    /** 
        @param $params Empty array
        @return Report
    **/
    public static function execute($params=[]): string{
        if(count($params) > 0){
            return "WRONG USAGE : useless parameter : {$params[0]}\n";
        }
        
        $report = '';
        
        $g = Group::getBySlug(Muller1083::GROUP_SLUG);
        $g-> computeMembers();
        
        $source = Muller1083::getSource();
        self::$sourceSlug = $source->data['slug'];

        $outfile = Config::$data['dirs']['output'] . DS . self::OUTPUT_DIR . DS . self::OUTPUT_FILE;
        
        $csvFields = [
            'MUID',
            'GQID',
            'FNAME',
            'GNAME',
            'OCCU',
            'DATE',
            'TZO',
            'DATE-UT',
            'PLACE',
            'C2',
            'C3',
            'CY',
            'LG',
            'LAT',
            'GEOID',
            // stuff coming from original raw file
            'ELECTDAT',
            'ELECTAGE',
            'STBDATUM',
            'SONNE',
            'MOND',
            'VENUS',
            'MARS',
            'JUPITER',
            'SATURN',
            'SO_',
            'MO_',
            'VE_',
            'MA_',
            'JU_',
            'SA_',
            'PHAS_',
            'AUFAB',
            'NIENMO',
            'NIENVE',
            'NIENMA',
            'NIENJU',
            'NIENSA',
        ];
        
        $map = [
            'name.given' => 'GNAME',
            'birth.date' => 'DATE',
            'birth.tzo' => 'TZO',
            'birth.date-ut' => 'DATE-UT',
            'birth.place.name' => 'PLACE',
            'birth.place.c2' => 'C2',
            'birth.place.c3' => 'C3',
            'birth.place.cy' => 'CY',
            'birth.place.lg' => 'LG',
            'birth.place.lat' => 'LAT',
            'birth.place.geoid' => 'GEOID',
            // stuff coming from original raw file
            'raw.' . self::$sourceSlug . '.ELECTDAT' => 'ELECTDAT',
            'raw.' . self::$sourceSlug . '.ELECTAGE' => 'ELECTAGE',
            'raw.' . self::$sourceSlug . '.STBDATUM' => 'STBDATUM',
            'raw.' . self::$sourceSlug . '.SONNE' => 'SONNE',
            'raw.' . self::$sourceSlug . '.MOND' => 'MOND',
            'raw.' . self::$sourceSlug . '.VENUS' => 'VENUS',
            'raw.' . self::$sourceSlug . '.MARS' => 'MARS',
            'raw.' . self::$sourceSlug . '.JUPITER' => 'JUPITER',
            'raw.' . self::$sourceSlug . '.SATURN' => 'SATURN',
            'raw.' . self::$sourceSlug . '.SO_' => 'SO_',
            'raw.' . self::$sourceSlug . '.MO_' => 'MO_',
            'raw.' . self::$sourceSlug . '.VE_' => 'VE_',
            'raw.' . self::$sourceSlug . '.MA_' => 'MA_',
            'raw.' . self::$sourceSlug . '.JU_' => 'JU_',
            'raw.' . self::$sourceSlug . '.SA_' => 'SA_',
            'raw.' . self::$sourceSlug . '.PHAS_' => 'PHAS_',
            'raw.' . self::$sourceSlug . '.AUFAB' => 'AUFAB',
            'raw.' . self::$sourceSlug . '.NIENMO' => 'NIENMO',
            'raw.' . self::$sourceSlug . '.NIENVE' => 'NIENVE',
            'raw.' . self::$sourceSlug . '.NIENMA' => 'NIENMA',
            'raw.' . self::$sourceSlug . '.NIENJU' => 'NIENJU',
            'raw.' . self::$sourceSlug . '.NIENSA' => 'NIENSA',
        ];
        
        $fmap = [
            'FNAME' => function($p){
                // ok because all members are french
                return Names_fr::computeFamilyName($p->data['name']['family'], $p->data['name']['nobiliary-particle']);
            },
            'GQID' => function($p){
                return $p->data['ids-in-sources']['cura'] ?? '';
            },
            'MUID' => function($p){
                return Newalch::ids_in_sources2muId($p->data['ids-in-sources']);
            },
            'OCCU' => function($p){
                return implode('+', $p->data['occus']);
            },
        ];
        
        // sorts by Müller id
        $sort = function($a, $b){
             return $a->data['ids-in-sources'][self::$sourceSlug] <=> $b->data['ids-in-sources'][self::$sourceSlug];
        };
        
        $filters = [];
        
        return $g->exportCsv($outfile, $csvFields, $map, $fmap, $sort, $filters);
    }
    
}// end class    

