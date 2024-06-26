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
 * OBU Forms - Redirect a user's form to a different authoriser
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./redirect_input.php');

require_login();

$home = new moodle_url('/');
if (!local_obu_forms_is_manager()) {
	redirect($home);
}

$forms_course = local_obu_forms_get_forms_course();
require_login($forms_course);
$back = $home . 'course/view.php?id=' . $forms_course;

if (!has_capability('local/obu_forms:update', context_system::instance())) {
	redirect($back);
}

// We only handle an existing form (id given)
if (isset($_REQUEST['id'])) {
	$data_id = $_REQUEST['id'];
} else {
	echo(get_string('invalid_data', 'local_obu_forms'));
	die;
}

// We may have the username of the authoriser
if (isset($_REQUEST['authoriser'])) {
	$authoriser = $_REQUEST['authoriser'];
	$user = get_complete_user_data('username', $authoriser);
	$authoriser_id = $user->id;
	$authoriser_name = $user->firstname . ' ' . $user->lastname;
} else {
	$authoriser = null;
	$authoriser_id = 0;
	$authoriser_name = null;
}

$dir = $home . 'local/obu_forms/';
$url = $dir . 'redirect.php?id=' . $data_id;

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('redirect_form', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

local_obu_forms_read_form_data($data_id, $data);
$template = local_obu_forms_read_form_template_by_id($data->template_id);
$form = local_obu_forms_read_form_settings($template->form_id);

// If a staff form, extract any given student number
$student_number = '';
if (!$form->student) {
    local_obu_forms_load_form_fields($data, $fields);
	if (array_key_exists('student_number', $fields)) {
		$student_number = ' [' . $fields['student_number'] . ']';
	}
}

local_obu_forms_get_form_status($USER->id, $form, $data, $text, $button); // get the authorisation trail and the next action (from the user's perspective)
$form_status = '<h4>' . $form->formref . ': ' . $form->name . $student_number . '</h4>' . $text;

$parameters = [
	'data_id' => $data_id,
	'form_status' => $form_status,
	'authoriser' => $authoriser,
	'authoriser_name' => $authoriser_name
];

// Check that they have both the authority and the ability to redirect this form
if (!local_obu_forms_is_manager($form) || ($data->authorisation_state > 0)) { // Already finally approved or rejected?
	$message = get_string('form_unavailable', 'local_obu_forms');
} else {
	$message = '';
}

$mform = new redirect_input(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_forms')) {
		if ($data->authorisation_level == 1) {
			$data->auth_1_id = $authoriser_id;
		} else if ($data->authorisation_level == 2) {
			$data->auth_2_id = $authoriser_id;
		} else if ($data->authorisation_level == 3) {
			$data->auth_3_id = $authoriser_id;
		} else if ($data->authorisation_level == 4) {
			$data->auth_4_id = $authoriser_id;
		} else if ($data->authorisation_level == 5) {
			$data->auth_5_id = $authoriser_id;
		} else {
			echo(get_string('invalid_data', 'local_obu_forms'));
			die;
		}
		$data->redirector_id = $USER->id;
		$data->redirection_date = time();
        local_obu_forms_write_form_data($data); // Update the form data record
        local_obu_forms_update_authoriser($form, $data, $authoriser_id); // Update the authorisations and send notification emails
		
		redirect($back);
	}
}	

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $back);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
