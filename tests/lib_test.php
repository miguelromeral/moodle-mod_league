<?php


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/league/lib.php');

class mod_league_lib_testcase extends externallib_advanced_testcase {

    public static function create_exercise($leagueid){
        $name = 'exercise_name';
        $statement = 'exercise_statement';
        $exerciseid = league_exercise_add_instance($name, $statement, $leagueid);
        return $exerciseid;
    }
    
}