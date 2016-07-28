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
 * OBU Forms - Add or amend a form's settings
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');
require_once('./forms_input.php');

require_login();
$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$url = $home . 'local/obu_forms/forms.php';
$context = context_system::instance();

$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('settings_title', 'local_obu_forms'));

$message = '';
$formref = '';
$record = null;
$form_indicator = 0;
$student_indicator = 0;

if (isset($_REQUEST['formref'])) {
	$formref = strtoupper($_REQUEST['formref']);
	$record = read_form_settings_by_ref($formref);
	if (($record !== false) && !is_manager($record)) {
		$message = get_string('form_unavailable', 'local_obu_forms');
	} else {
		if (!has_capability('local/obu_forms:manage_ump_students', $context) && !has_capability('local/obu_forms:manage_ump_staff', $context)) {
			$form_indicator = 1; // Can only set UMP flag to false
		} else if (!has_capability('local/obu_forms:manage_pg', $context)) {
			$form_indicator = 2; // Can only set UMP flag to true
		}
		if (!has_capability('local/obu_forms:manage_ump_students', $context) && has_capability('local/obu_forms:manage_ump_staff', $context)) {
			$student_indicator = 1; // Can only set student flag to false
		} else if (has_capability('local/obu_forms:manage_ump_students', $context) && !has_capability('local/obu_forms:manage_ump_staff', $context)) {
			$student_indicator = 2; // Can only set student flag to true
		}
	}
}

$parameters = [
	'formref' => $formref,
	'record' => $record,
	'form_indicator' => $form_indicator,
	'student_indicator' => $student_indicator
];

$mform = new settings_input(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_forms')) {
		write_form_settings($USER->id, $mform_data);
		redirect($url);
    }
}	

echo $OUTPUT->header();

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
