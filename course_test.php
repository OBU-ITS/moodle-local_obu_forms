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
 * OBU Forms - Course test
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./course_test_input.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}
$dir = $home . 'local/obu_forms/';

$forms_course = get_forms_course();
require_login($forms_course);
$back = $home . 'course/view.php?id=' . $forms_course;

$url = $dir . 'course_test.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('course_test', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$mform = new course_test_input(null);

if ($mform->is_cancelled()) {
    redirect($back);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($mform_data = (array)$mform->get_data()) {
	$courses = get_current_courses(false, 0, true, false); // modular?, user_id, names?, joint?);

	foreach ($courses as $id => $text) {
		echo $text . '<br>'; 
	}
}
else
{
	$mform->display();
}

echo $OUTPUT->footer();
