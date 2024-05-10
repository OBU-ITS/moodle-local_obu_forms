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
 * OBU Forms - Set up the forwarding of forms from one authoriser to another
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./forward_form.php');

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

$dir = $home . 'local/obu_forms/';
$url = $dir . 'forward.php';
$check = $dir . 'forward_check.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('forward_forms', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';
$from = '';
$to = '';
$start_date = 0;
$stop_date = 0;

if (isset($_REQUEST['authoriser'])) {
	$forwarder = local_obu_forms_read_form_forwarder($_REQUEST['authoriser']);
	if ($forwarder->id != 0) {
		$user = get_complete_user_data('id', $forwarder->from_id);
		$from = $user->username;
		$user = get_complete_user_data('id', $forwarder->to_id);
		$to = $user->username;
		$start_date = $forwarder->start_date;
		$stop_date = $forwarder->stop_date;
	}
}

$parameters = [
	'from' => $from,
	'to' => $to,
	'start_date' => $start_date,
	'stop_date' => $stop_date
];

$mform = new forward_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_forms')) {
		$from = get_complete_user_data('username', $mform_data->from);
		if ($mform_data->to == '') { // Blank 'to' implies 'delete'
            local_obu_forms_delete_form_forwarder($from->id);
		} else {
			$to = get_complete_user_data('username', $mform_data->to);
            local_obu_forms_write_form_forwarder($from->id, $to->id, $mform_data->start_date, $mform_data->stop_date);
		}
		redirect($check);
    }
}	

echo $OUTPUT->header();

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
	echo get_string('forward_text', 'local_obu_forms');
}

echo $OUTPUT->footer();
