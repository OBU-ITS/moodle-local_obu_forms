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
 * OBU Forms - Add or amend a form template
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
require_once('./template_input.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$forms_course = get_forms_course();
require_login($forms_course);
$back = $home . 'course/view.php?id=' . $forms_course;

if (!has_capability('local/obu_forms:update', context_system::instance())) {
	redirect($back);
}

$dir = $home . 'local/obu_forms/';
$url = $dir . 'template.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('template_title', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$formref = '';
$form_id = '';
$formname = '';
$version = '';
$versions = array();
$record = null;
$is_published = 0;

if (isset($_REQUEST['formref'])) {
	$formref = strtoupper($_REQUEST['formref']);
	$settings = read_form_settings_by_ref($formref);
	if ($settings === false) {
		$message = get_string('invalid_data', 'local_obu_forms');
	} else if (!is_manager($settings)) { // Not a manager of this type of form
		$message = get_string('form_unavailable', 'local_obu_forms');
	} else {
		if ($formref != '') {
			$PAGE->navbar->add(get_string('form', 'local_obu_forms') . ' ' . $formref);
		}
		$form_id = $settings->id;
		$formname = $settings->name;
		if (isset($_REQUEST['version'])) {
			$version = strtoupper($_REQUEST['version']);
		} else {
			if (!isset($_REQUEST['versions']) || (isset($_REQUEST['versions']) && $_REQUEST['versions'] != 0)) {
				$templates = read_form_templates($form_id);
				if ($templates) {
					$versions[0] = get_string('new_version', 'local_obu_forms'); // The 'New Version' option
					foreach ($templates as $template) {
						$versions[] = $template->version;
					}
					if (isset($_REQUEST['versions'])) {
						$version = $versions[$_REQUEST['versions']];
						$record = read_form_template($form_id, $version);
					}
				}
			}
		}
	}
}

$parameters = [
	'formref' => $formref,
	'formname' => $formname,
	'version' => $version,
	'versions' => $versions,
	'record' => $record
];

$mform = new template_input(null, $parameters);

if ($mform->is_cancelled()) {
	if ($formref == '') {
		redirect($back);
	} else {
		redirect($url);
	}
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_forms')) {
		if (!$mform_data->already_published || is_siteadmin()) {
			write_form_template($USER->id, $mform_data);
		}
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
