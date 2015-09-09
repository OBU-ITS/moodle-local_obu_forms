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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./form_view.php');

require_login();
$context = context_system::instance();
$manager = has_capability('local/obu_forms:manage', $context);
$staff = (substr($USER->idnumber, 0, 1) == 'p');

$home = new moodle_url('/');
$dir = '/local/obu_forms/';
$program = $dir . 'form.php';
$url = $home . $program;
$process_url = $home . $dir . 'process.php';

$PAGE->set_pagelayout('standard');
$PAGE->set_url($program);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('form_title', 'local_obu_forms'));

// Initialise values
$data_id = 0;
$fields = array();

if (isset($_REQUEST['ref'])) { // A request for a brand new form
	$settings = read_form_settings_by_ref($_REQUEST['ref']);
	if ($settings === false) {
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}
	if (!$manager && ((!$settings->student && !$staff) || !$settings->visible)) { // User hasn't the capability to view a non-student or hidden form
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}
	if (isset($_REQUEST['version'])) {
		$template = read_form_template($settings->id, $_REQUEST['version']);
	} else {
		// Get the relevant form template (include draft templates if an administrator)
		$template = get_form_template($settings->id, is_siteadmin());
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
		echo(get_string('invalid_data', 'local_obu_forms'));
		die;
	}
	$template = read_form_template_by_id($record->template_id);
	$settings = read_form_settings($template->form_id);
} else if (isset($_REQUEST['template'])) { // A form being created/amended
	$template_id = $_REQUEST['template'];
	if ($template_id == 0) {
		redirect($home);
	}
	$template = read_form_template_by_id($template_id);
	$settings = read_form_settings($template->form_id);
} else {
	echo(get_string('invalid_data', 'local_obu_forms'));
	die;
}

$current_course = '';
$button_text = 'submit';
if ($settings->student) { // A student form - the user must be enrolled in a current PG course (programme) in order to submit it
	$course = get_current_courses('P', $USER->id);
	$current_course = current($course);
	if ($current_course === false) {
		if ($manager || $staff) { // Let them view, but not submit, the form
			$button_text = 'cancel';
		} else {
			echo(get_string('invalid_data', 'local_obu_forms'));
			die;
		}
	}
}

$PAGE->navbar->add(get_string('form', 'local_obu_forms') . ' ' . $settings->formref);

// Find any 'select' fields so that we can prepare the options
$start_dates = array();
$start_selected = 0;
$adviser = array();
$course = array();
$not_enroled = array();
$enroled = array();
$study_mode = array();
$reason = array();
$selects = template_selects($template->data);
foreach ($selects as $select) {
	switch ($select) {
		case 'start_dates':
			if (empty($start_dates)) {
				// Get an array of possible module start dates (6 months back and 12 months forward from today)
				$start_dates = get_dates(date('m'), date('y'), 6, 12);
				$start_selected = 6;
			}
			break;
		case 'adviser':
			if (empty($adviser)) {
				$adviser = get_advisers($USER->id);
			}
			break;
		case 'course':
			if (empty($course)) {
				$course = get_current_courses('P', $USER->id);
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
		case 'study_mode':
			if (empty($study_mode)) {
				$study_mode = get_study_modes();
			}
			break;
		case 'reason':
			if (empty($study_mode)) {
				$reason = get_reasons();
			}
			break;
		default:
	}
}

$parameters = [
	'data_id' => $data_id,
	'template' => $template,
	'username' => $USER->username,
	'surname' => $USER->lastname,
	'forenames' => $USER->firstname,
	'current_course' => $current_course,
	'start_dates' => $start_dates,
	'start_selected' => 6,
	'adviser' => $adviser,
	'course' => $course,
	'not_enroled' => $not_enroled,
	'enroled' => $enroled,
	'study_mode' => $study_mode,
	'reason' => $reason,
	'fields' => $fields,
	'button_text' => $button_text
];
	
$message = '';

$mform = new form_view(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = (array)$mform->get_data()) {
	$fields = array();
	foreach ($mform_data as $key => $value) {
		// ignore the mandatory and standard fields (but keep 'template' for completeness)
		if (($key == 'id') || ($key == 'ref') || ($key == 'version') || ($key == 'submitbutton')) {
			continue;
		}
		
		// select from the options if required
		$select = $selects[$key];
		switch ($select) {
			case 'start_dates':
				$value = $start_dates[$value];
				break;
			case 'adviser':
				$value = $adviser[$value];
				break;
			case 'course':
				$value = $course[$value];
				break;
			case 'not_enroled':
				$value = $not_enroled[$value];
				break;
			case 'enroled':
				$value = $enroled[$value];
				break;
			case 'study_mode':
				$value = $study_mode[$value];
				break;
			case 'reason':
				$value = $reason[$value];
				break;
			default:
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
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
