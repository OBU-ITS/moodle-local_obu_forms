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
 * OBU Forms - Date test
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./date_test_input.php');

$months = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}
$dir = $home . 'local/obu_forms/';

$forms_course = get_forms_course();
require_login($forms_course);
$back = $home . 'course/view.php?id=' . $forms_course;

$url = $dir . 'date_test.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('date_test', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$date = '';
$dates = array();

if (isset($_REQUEST['date'])) {
	$month = substr($_REQUEST['date'], 0, 2);
	$year = substr($_REQUEST['date'], 2);
	$date = $months[$month - 1] . ' ' . $year;
	$dates = get_dates($month, $year);
}

$parameters = [
	'date' => $date,
	'dates' => $dates
];

$mform = new date_test_input(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

/*
if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('submit', 'local_obu_forms')) {
		$month = substr($data['date'], 0, 2);
		$year = 2000 + substr($data['date'], 2);
		$dates = get_dates($month, $year);
		echo $months[$month] + ' ' + $year;
		foreach($dates as $date) {
			echo $date;
		}
    }
}	
*/
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$mform->display();

echo $OUTPUT->footer();
