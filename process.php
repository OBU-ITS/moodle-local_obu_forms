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
 * OBU Forms - Process a form
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./form_view.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

// We only handle an existing form (id given)
if (!isset($_REQUEST['id'])) {
	echo(get_string('invalid_data', 'local_obu_forms'));
	die;
}

$data_id = $_REQUEST['id'];
if (!load_form_data($data_id, $record, $fields)) {
	echo(get_string('invalid_data', 'local_obu_forms'));
	die;
}

$home = new moodle_url('/');
$dir = $home . 'local/obu_forms/';
$program = $dir . 'process.php?id=' . $data_id;
$redirect_form = $dir . 'redirect.php?id=' . $data_id;

$PAGE->set_url($program);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('form_title', 'local_obu_forms'));

$template = read_form_template_by_id($record->template_id);
$settings = read_form_settings($template->form_id);

$PAGE->navbar->add(get_string('form', 'local_obu_forms') . ' ' . $settings->formref);

$message = '';

// If not awaiting authorisation by someone, display the current status (prominently)
if (($record->authorisation_state == 0) && ($record->authorisation_level == 0)) { // Form not yet submitted
	if ($USER->id != $record->author) { // no-one else can look at your unsubmitted forms
		$message = get_string('form_unavailable', 'local_obu_forms');
	}
	$status_text = get_string('status_not_submitted', 'local_obu_forms');
	
	// For the time being, we auto-submit the form to avoid a two-stage process for the author (might change?)
	update_workflow(true);
	$status_text = '';
	
} else if ($record->authorisation_state == 1) { // Form rejected
	$status_text = get_string('status_rejected', 'local_obu_forms');
} else if ($record->authorisation_state == 2) { // Form processed
	$status_text = get_string('status_processed', 'local_obu_forms');
} else {
	$status_text = '';
}
if ($status_text) {
	$status_text = '<h3>' . $status_text . '</h3>';
}

get_form_status($USER->id, $record, $text, $button_text); // get the authorisation trail and the next action (from the user's perspective)
$status_text .= $text;

if ($button_text != 'authorise') { // If not the next authoriser, check that this user can view the form
	if (!is_manager($settings) && ($USER->id != $record->author)) {
		$message = get_string('form_unavailable', 'local_obu_forms');
	}
} else { // Display any notes prepared for the authoriser
	$text = '';
	if ($record->authorisation_level == 1) {
		$text = $settings->auth_1_notes;
	} else if ($record->authorisation_level == 2) {
		$text = $settings->auth_2_notes;
	} else {
		$text = $settings->auth_3_notes;
	}
	if ($text) {
		$text = '<h4>' . $text . '</h4>';
		$status_text .= $text;
	}
}

$parameters = [
	'data_id' => $data_id,
	'template' => $template,
	'username' => null,
	'surname' => null,
	'forenames' => null,
	'current_course' => null,
	'start_dates' => null,
	'start_selected' => null,
	'adviser' => null,
	'course' => null,
	'not_enroled' => null,
	'enroled' => null,
	'study_mode' => null,
	'reason' => null,
	'fields' => $fields,
	'auth_state' => $record->authorisation_state,
	'auth_level' => $record->authorisation_level,
	'status_text' => $status_text,
	'button_text' => $button_text
];
	
$mform = new form_view(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if (($mform_data->redirectbutton == get_string('redirect', 'local_obu_forms')) && is_manager($settings) && ($record->authorisation_state == 0)) { // They want to redirect the form
		redirect($redirect_form);
	} else if (($button_text == 'authorise') && ($mform_data->submitbutton != get_string('continue', 'local_obu_forms')) // They can do something (and they want to)
		&& ($mform_data->auth_state == $record->authorisation_state) && ($mform_data->auth_level == $record->authorisation_level)) { // Check nothing happened while we were away (or they clicked twice)
		if ($mform_data->rejectbutton != get_string('reject', 'local_obu_forms')) {
			update_workflow(true, $mform_data->comment);
		} else {
			update_workflow(false, $mform_data->comment);
		}
	}

	if ($USER->id == $record->author) { // Looking at their own form
		redirect($dir);
	} else {
		redirect($home);
	}
}

echo $OUTPUT->header();

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();

function update_workflow($authorised = true, $comment = null) {
	global $settings, $record, $fields;
	
	// Update the form data record
	$authoriser_id = 0;
	if ($record->authorisation_level == 0) { // Being submitted
		$record->authorisation_level = 1;
		$authoriser_id = get_authoriser($record->author, $settings->auth_1_role, $fields);
		$record->auth_1_id = $authoriser_id;
	} else if ($record->authorisation_level == 1) {
		$record->auth_1_notes = $comment;
		$record->auth_1_date = time();
		if (!$authorised) {
			$record->authorisation_state = 1; // Rejected
		} else if ($settings->auth_2_role == 0) {
			$record->authorisation_state = 2; // It ends here
		} else {
			$record->authorisation_level = 2;
			$authoriser_id = get_authoriser($record->author, $settings->auth_2_role, $fields);
			$record->auth_2_id = $authoriser_id;
		}
	} else if ($record->authorisation_level == 2) {
		$record->auth_2_notes = $comment;
		$record->auth_2_date = time();
		if (!$authorised) {
			$record->authorisation_state = 1; // Rejected
		} else if ($settings->auth_3_role == 0) {
			$record->authorisation_state = 2; // It ends here
		} else {
			$record->authorisation_level = 3;
			$authoriser_id = get_authoriser($record->author, $settings->auth_3_role, $fields);
			$record->auth_3_id = $authoriser_id;
		}
	} else {
		$record->auth_3_notes = $comment;
		$record->auth_3_date = time();
		if (!$authorised) {
			$record->authorisation_state = 1; // Rejected
		} else {
			$record->authorisation_state = 2; // It ends here
		}
	}
	save_form_data($record, $fields);
	
	// Update the stored authorisation requests and send notification emails
	update_authoriser($settings, $record, $authoriser_id);
}
