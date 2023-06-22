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
 * OBU Forms - Form handler
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./form_view.php');

require_login();

$home = new moodle_url('/');
$dir = $home . 'local/obu_forms/';
if (is_manager()) {
	$forms_course = get_forms_course();
	require_login($forms_course);
	$back = $home . 'course/view.php?id=' . $forms_course;
} else {
	$PAGE->set_context(context_user::instance($USER->id));
	$back = $dir . 'menu.php';
}

if (!has_capability('local/obu_forms:update', context_system::instance())) {
	redirect($back);
}

$url = $dir . 'form.php';
$process_url = $dir . 'process.php';

$title = get_string('forms_management', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Initialise values
$message = '';
$data_id = 0;
$fields = array();

$staff = (((substr($USER->username, 0, 1) == 'p') || (substr($USER->username, 0, 1) == 'd')) && is_numeric(substr($USER->username, 1)));

if (isset($_REQUEST['ref'])) { // A request for a brand new form
	$form = read_form_settings_by_ref($_REQUEST['ref']);
	if ($form === false) {
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}

	if (!is_manager($form) && ((!$form->student && !$staff) || !$form->visible)) { // User hasn't the capability to view a non-student or hidden form
		$message = get_string('form_unavailable', 'local_obu_forms');
	}
	if (isset($_REQUEST['version'])) {
		$template = read_form_template($form->id, $_REQUEST['version']);
	} else {
		// Get the relevant form template (include draft templates if an administrator)
		$template = get_form_template($form->id, is_siteadmin());
	}
	if (!$template) {
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}
} else if (isset($_REQUEST['id'])) { // An existing form
	$data_id = $_REQUEST['id'];
	if ($data_id == 0) {
		redirect($home);
	}
	if (!load_form_data($data_id, $record, $fields)) {
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}
	if ($USER->id != $record->author) { // no-one else can amend your forms
		$message = get_string('form_unavailable', 'local_obu_forms');
	}
	$template = read_form_template_by_id($record->template_id);
	$form = read_form_settings($template->form_id);
} else if (isset($_REQUEST['template'])) { // A form being created/amended
	$template_id = $_REQUEST['template'];
	if ($template_id == 0) {
		redirect($home);
	}
	$template = read_form_template_by_id($template_id);
	$form = read_form_settings($template->form_id);
} else {
	echo(get_string('invalid_data', 'local_obu_forms'));
	die;
}

$current_course = '';
$button_text = 'submit';
if ($form->student) { // A student form - the user must be enrolled in a current course (programme) of the right type in order to submit it
	$course = get_current_courses($form->modular, $USER->id);
	$current_course = current($course); // We're assuming only one
	if ($current_course === false) {
		if (is_manager($form) || $staff) { // Let them view, but not submit, the form
			$button_text = 'cancel';
		} else {
			$message = get_string('form_unavailable', 'local_obu_forms');
		}
	}
}

$PAGE->navbar->add(get_string('form', 'local_obu_forms') . ' ' . $form->formref);

// Find any 'select' fields so that we can prepare the options
$start_dates = array();
$start_selected = 0;
$adviser = array();
$supervisor = array();
$course = array();
$course_joint = array();
$not_enroled = array();
$enroled = array();
$free_language = array();
$campus = array();
$study_mode = array();
$reason = array();
$addition_reason = array();
$deletion_reason = array();
$assessment_type = array();
$component_comment = array();
$selects = template_selects($template->data);

foreach ($selects as $select) {
	switch ($select) {
		case 'start_dates':
			if (empty($start_dates)) {
				// Get an array of possible module start dates
				if ($form->modular) { // This academic year and next
					$start_dates = get_dates(date('m'), date('y'));
					$start_selected = 0; // Default to the start of the year
				} else { // 6 months back and 12 months forward from today
					$start_dates = get_dates(date('m'), date('y'), 6, 12);
//					$start_selected = 6; // Default to this month
					$start_selected = 0; // Now we say 'Please select'
				}
			}
			break;
		case 'adviser':
			if (empty($adviser)) {
				$adviser = get_advisers($form->modular, $USER->id);
			}
			break;
		case 'supervisor':
			if (empty($supervisor)) {
				$supervisor = get_supervisors($USER->id);
			}
			break;
		case 'course':
			if (empty($course)) {
				$course = get_current_courses($form->modular, 0, true, false);
			}
			break;
		case 'course_joint':
			if (empty($course_joint)) {
				$course_joint = array_merge(array('0' => ''), get_current_courses($form->modular, 0, true, true));
			}
			break;
		case 'not_enroled':
			if (empty($not_enroled)) {
				$not_enroled = get_current_modules(0, null, $USER->id, false);
			}
			break;
		case 'enroled':
			if (empty($enroled)) {
				$enroled = get_current_modules(0, null, $USER->id, true);
			}
			break;
		case 'free_language':
			if (empty($free_language)) {
				$free_language = get_current_modules(0, null, 0, false, true);
			}
			break;
		case 'campus':
			if (empty($campus)) {
				$campus = get_campuses();
			}
			break;
		case 'study_mode':
			if (empty($study_mode)) {
				$study_mode = get_study_modes();
			}
			break;
		case 'reason':
			if (empty($reason)) {
				$reason = get_reasons();
			}
			break;
		case 'addition_reason':
			if (empty($addition_reason)) {
				$addition_reason = get_addition_reasons();
			}
			break;
		case 'deletion_reason':
			if (empty($deletion_reason)) {
				$deletion_reason = get_deletion_reasons();
			}
			break;
		case 'assessment_type':
			if (empty($assessment_type)) {
				$assessment_type = get_assessment_types();
			}
			break;
		case 'component_comment':
			if (empty($component_comment)) {
				$component_comment = get_component_comments();
			}
			break;
		default:
	}
}

$parameters = [
	'modular' => $form->modular,
	'data_id' => $data_id,
	'template' => $template,
	'username' => $USER->username,
	'surname' => $USER->lastname,
	'forenames' => $USER->firstname,
	'current_course' => $current_course,
	'start_dates' => $start_dates,
	'start_selected' => $start_selected,
	'adviser' => $adviser,
	'supervisor' => $supervisor,
	'course' => $course,
	'course_joint' => $course_joint,
	'not_enroled' => $not_enroled,
	'enroled' => $enroled,
	'free_language' => $free_language,
	'campus' => $campus,
	'study_mode' => $study_mode,
	'reason' => $reason,
	'addition_reason' => $addition_reason,
	'deletion_reason' => $deletion_reason,
	'assessment_type' => $assessment_type,
	'component_comment' => $component_comment,
	'fields' => $fields,
	'auth_state' => null,
	'auth_level' => null,
	'status_text' => null,
	'notes' => null,
	'button_text' => $button_text
];

$mform = new form_view(null, $parameters);

if ($mform->is_cancelled() || !has_capability('local/obu_forms:update', context_system::instance())) {
    redirect($back);
}

if ($mform_data = (array)$mform->get_data()) {
	$fields = array();
	foreach ($mform_data as $key => $value) {
		// ignore the mandatory and standard fields (but keep 'template' for completeness)
		if (($key == 'id') || ($key == 'ref') || ($key == 'version') || ($key == 'submitbutton')) {
			continue;
		}

		// select from the options if required
		if (array_key_exists($key, $selects)) {
			switch ($selects[$key]) {
				case 'start_dates':
					if ($value == '0') { // Default is 'Please select'
						$value = '';
					} else {
						$value = $start_dates[$value];
					}
					break;
				case 'adviser':
					if ($value == '0') { // Default is 'Please select'
						$value = '';
					} else {
						$value = $adviser[$value];
					}
					break;
				case 'supervisor':
					if ($value == '0') { // Default is 'Please select'
						$value = '';
					} else {
						$value = $supervisor[$value];
					}
					break;
				case 'course':
					$value = $course[$value];
					break;
				case 'course_joint':
					$value = $course_joint[$value];
					break;
				case 'not_enroled':
					$value = $not_enroled[$value];
					break;
				case 'enroled':
					$value = $enroled[$value];
					break;
				case 'free_language':
					$value = $free_language[$value];
					break;
				case 'campus':
					$value = $campus[$value];
					break;
				case 'study_mode':
					$value = $study_mode[$value];
					break;
				case 'reason':
					$value = $reason[$value];
					break;
				case 'addition_reason':
					$value = $addition_reason[$value];
					break;
				case 'deletion_reason':
					$value = $deletion_reason[$value];
					break;
				case 'assessment_type':
					$value = $assessment_type[$value];
					break;
				case 'component_comment':
					$value = $component_comment[$value];
					break;
				default:
			}
		}

		$fields[$key] = $value;
	}

    $record = new stdClass();
    $record->id = $data_id;
    $record->author = $USER->id;
    $record->template_id = $template->id;
	$record->date = time();
    $record->authorisation_state = 0; // Awaiting submission/authorisation...
    $record->authorisation_level = 0; // ...by author
	$data_id = save_form_data($record, $fields);
	redirect($process_url . '?id=' . $data_id); // Perform initial form processing
}

echo $OUTPUT->header();

if ($message) {
    notice($message, $home);
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
