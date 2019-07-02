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
 * OBU Forms - Get a user ID and list all their forms
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
require_once('./user_input.php');

require_login();

$home = new moodle_url('/');
$dir = $home . 'local/obu_forms/';

// Can only list someone else's forms if we are a form manager or a member of staff
if (is_manager()) {
	$forms_course = get_forms_course();
	require_login($forms_course);
	$back = $home . 'course/view.php?id=' . $forms_course;
} else {
	$back = $dir . 'menu.php';
	if (!is_staff($USER->username)) {
		redirect($back);
	}
}

$url = $dir . 'list.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('list_users_forms', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$mform = new user_input(null, array());

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
	$user = get_complete_user_data('username', $mform_data->username);
	$url = $dir . 'index.php?userid=' . $user->id;
	redirect($url);
}	

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
