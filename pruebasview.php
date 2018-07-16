
<?php

require_once('../../config.php');
require_once('lib.php');
require_once('utilities.php');

//Identifica la actividad específica (o recurso)
$cmid = required_param('id', PARAM_INT);    // Course Module ID
$cm = get_coursemodule_from_id('league', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

/*
 * ABSOLUTAMENTE NECESARIO PONER EL URL.
 * Por lo menos, el id, después se pueden poner otras 'key' => 'value'
 * Convierte todo lo que le pasamos a un objeto moodle_url
 */
$PAGE->set_url('/mod/league/pruebasview.php', array('id' => $cm->id));

if ($cmid) {
    if (!$cm = get_coursemodule_from_id('league', $cmid)) {
        print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
    }
    if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
        print_error('course is misconfigured');  // NOTE As above
    }
    if (!$league = $DB->get_record('league', array('id'=> $cm->instance))) {
        print_error('course module is incorrect'); // NOTE As above
    }
    require_course_login($course, true, $cm);
} else {
    print_error('missingparameter');
}


/*
 * ABSOLUTAMENTE NECESARIO. Podriamos poner:
        $PAGE->set_context(context_system::instance());
        $PAGE->set_context(context_coursecat::instance($categoryid));
        $PAGE->set_context(context_course::instance($courseid));
        $PAGE->set_context(context_module::instance($moduleid));
 * dependiendo de nuestras necesidades
 */
$context = context_module::instance($cm->id);


/*
$params = array(

    'objectid' => $league->id,
    'context' => $context
);

$event = \mod_league\event\course_module_viewed::create($params);
$event->trigger();
*/


$PAGE->set_context($context);

//Pone como diseño el estandar de Moodle
$PAGE->set_pagelayout('standard');

// Mark viewed if required
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print header.
$PAGE->set_title(format_string($league->name));
$PAGE->set_heading(format_string($course->fullname).": ".$league->name);

$buttonqualy = '<form action="qualy.php" method="get">
                    <input type="hidden" name="id" value="'. $cmid .'" />
                    <input type="submit" value="'. get_string('view_qualy_button', 'league') .'"/>
                </form>';
$PAGE->set_button($buttonqualy);



/// Some capability checks.
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    notice(get_string("activityiscurrentlyhidden"));
}

//$mod = mod_league\league($cm);
        
if (!has_capability('mod/league:view', $context, $USER->id)) {
    notice(get_string('noviewdiscussionspermission', 'league'));
}

/// find out current groups mode
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/league/view.php?id=' . $cm->id);
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

$var="SELECT * 
FROM mdl_role as er
INNER JOIN mdl_role_assignments as era 
ON era.roleid=er.id
WHERE userid = $USER->id";
$data = $DB->get_records_sql($var);
$rol = null;
foreach ($data as $rowclass)
{
    $rowclass = json_decode(json_encode($rowclass), True);
    switch ($rowclass['shortname']){
        case 'student':
            $rol = 'student';
            break;
        case 'teacher' || 'editingteacher':
            $rol = 'teacher';
            break;
    }
}

$output = $PAGE->get_renderer('mod_league');

echo $output->header();

$exercises = get_exercises_from_id($league->id);

if ($rol == 'student'){
    
    $notas = get_notas_alumno($league->id, $cmid, $USER->id, $context->id);
    $panel = new main_view($exercises, $cmid, $context->id, 'student', $notas);
    echo $output->render($panel);
    
    
    
}else if(has_capability('mod/league:view', $context, $USER->id) || $rol == 'teacher'){
    
    
    $panel = new main_view($exercises, $cmid, $context->id, 'teacher');
    echo $output->render($panel);
    
    
    $action = optional_param('action', 'no-act', PARAM_TEXT);
        
    if($action != 'no-act')
    {
        
        
        $id_exer_post = required_param('id_exer', PARAM_INT);
        $exer_name_post = required_param('exer_name', PARAM_TEXT);
        $exer_description_post = required_param('exer_description', PARAM_TEXT);
        $exer_enabled_post = required_param('exer_enabled', PARAM_INT);
        $pub = required_param('exer_published', PARAM_INT);
        $course = $cm->course;
        $league_post = $league->id;

        if ($action == 'delete'){
            
            $exito = false;
            
            //Si está deshabilitado, podremos eliminarlo
            if ($exer_enabled_post == 0){
                $exito = league_exercise_delete_instance($id_exer_post, $context);
            }
            
            ?>
            <div>
                <strong><?php
                if ($exito){
                    echo get_string('exercise_deleted', 'league');
                } else {
                    echo get_string('exercise_not_deleted', 'league');
                }
                ?></strong><br>
            </div>
            <?php
            
        } else if ($action == 'enable_disable'){

            //Negamos el cambio, si estába des, lo activamos, y si estaba activado, lo des.
            $cambio = ($exer_enabled_post == 0 ? 1 : 0);
            
            league_exercise_update_instance($league, $course, $exer_name_post, $exer_description_post, $league_post, $id_exer_post, $cambio, $pub, $context);

            ?>
            <div>
                <strong><?php
                if ($cambio == 0){
                    echo get_string('exercise_disabled', 'league');
                } else {
                    echo get_string('exercise_enabled', 'league');
                }
                ?></strong><br>
            </div>
            <?php
        } else if ($action == 'publish'){

            //Negamos la accion qeu esta ahora
            $cambio = ($pub == 0 ? 1 : 0);
            league_exercise_update_instance($league, $course, $exer_name_post, $exer_description_post, $league_post, $id_exer_post, $exer_enabled_post, $cambio, $context);
            ?>
            <div>
                <strong><?php
                if ($cambio == 0){
                    echo get_string('currently_unpublished', 'league');
                } else {
                    echo get_string('currently_published', 'league');
                }
                ?></strong><br>
            </div>
            <?php
        }
        
    }
    
    ?>
    
    <form action="add_exercise.php" method="get">
        <input type="hidden" name="id" value="<?= $cmid ?>" />
        <input type="hidden" name="id_exer" value="-1" />
        <input type="submit" value="<?= get_string('add_exercise_button', 'league') ?>"/>
    </form>
    
    

    <?php
}else{
    //notice(get_string('noviewdiscussionspermission', 'league'));
}

echo $output->footer();

?>