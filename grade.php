<!DOCTYPE html>
<?php

require_once('../../config.php');
require_once('lib.php');
require_once('utilities.php');
require_once('./forms.php');

//Identifica la actividad específica (o recurso)
$cmid = required_param('id', PARAM_INT);    // Course Module ID
$itemnumber = optional_param('itemnumber', 0, PARAM_INT); // Item number, may be != 0 for activities that allow more than one grade per user
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional)
$cm = get_coursemodule_from_id('league', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$PAGE->set_url('/mod/league/grade.php', array('id' => $cm->id));

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

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

//Pone como diseño el estandar de Moodle
$PAGE->set_pagelayout('standard');

// Mark viewed if required
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print header.
$PAGE->set_title(format_string(get_string('title_grade', 'league')));
$PAGE->set_heading(format_string($course->fullname));

/// Some capability checks.
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    notice(get_string("activityiscurrentlyhidden"));
}

if (!has_capability('mod/league:view', $context) || ($userid && $userid != $USER->id)) {
    notice(get_string('noviewdiscussionspermission', 'league'));
}

/// find out current groups mode
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/league/grade.php?id=' . $cm->id);
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

$bc = new block_contents();

$rol = get_role_user($USER->id);

$output = $PAGE->get_renderer('mod_league');

echo $output->header();


if ($rol == 'student'){
    
    $notas = get_notas_alumno($league->id, $cmid, $USER->id, $context->id);
    $panel = new main_view(null, $cmid, $context->id, 'student', 'grades', $notas);
    echo $output->render($panel);
    
}else if($rol == 'teacher'){
    
    redirect('qualy.php?id='.$cmid);
    
}else{
    notice(get_string('noviewdiscussionspermission', 'league'));
}

echo $output->footer();

?>