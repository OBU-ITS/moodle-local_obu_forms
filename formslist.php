<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OBU Forms - List all available forms
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');

require_login();

$home = new moodle_url('/');
if (is_manager()) {
	$forms_course = get_forms_course();
	require_login($forms_course);
	$back = $home . 'course/view.php?id=' . $forms_course;
} else {
	$PAGE->set_context(context_user::instance($USER->id));
	$back = $home;
}

if (!has_capability('local/obu_forms:update', context_system::instance())) {
	redirect($back);
}

$type = optional_param('type', '', PARAM_TEXT);

$dir = $home . 'local/obu_forms/';
$url = $dir . 'formslist.php?type=' . $type;
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
if ($type == 'staff') {
	$heading = get_string('staff_forms', 'local_obu_forms');
} else if ($type == 'student') {
	$heading = get_string('student_forms', 'local_obu_forms');
} else {
	$heading = get_string('formslist', 'local_obu_forms');
}
$title = get_string('forms_management', 'local_obu_forms');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$staff_forms = (((substr($USER->username, 0, 1) == 'p') || (substr($USER->username, 0, 1) == 'd')) && is_numeric(substr($USER->username, 1))); // Can view staff forms
$pg_forms = $staff_forms || is_student($USER->id, 'PG'); // Can view PG student forms
$ump_forms = $staff_forms || is_student($USER->id, 'UMP'); // Can view UMP student forms

//check if program and then retrieve campus from here, display forms based on campus
$courses = get_current_course_id_number(false, $USER->id);
$courseId = current($courses);
$campusCode = strtok($courseId, "~");
$partnershipCampusCodes = array("AW", "SH", "SW", "AL", "BR", "BW", "WT", "OCE", "SB", "DM", "GBB", "GBE", "GBL", "GBM", "GBW");

if (empty($campusCode) || in_array($campusCode, $partnershipCampusCodes)){
    $pg_forms = false;
    $ump_forms = false;
}

if ($type == 'student') { // Exclude staff forms
	$staff_forms = false;
}

if ($type == 'staff') { // Exclude student forms
	$pg_forms = false;
	$ump_forms = false;
}

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forms = get_forms(is_manager(), $staff_forms, $pg_forms, $ump_forms);

$url = new moodle_url('/local/obu_forms/form.php');
foreach ($forms as $form) {
	echo '<h3><a href="' . $url . '?ref=' . $form->formref . '">' . $form->formref . ': ' . $form->name . '</a></h3>';
	echo $form->description;
}

echo $OUTPUT->footer();
