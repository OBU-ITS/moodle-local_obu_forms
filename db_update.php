<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more settings.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OBU Forms - db updates acting on the local_obu_forms tables
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function get_forms_course() {
	global $DB;

	$course = $DB->get_record('course', array('idnumber' => 'SUBS_FORMS'), 'id', MUST_EXIST);
	return $course->id;
}

// Check if the given user has the given role in the forms management course
function has_forms_role($user_id = 0, $role_id_1 = 0, $role_id_2 = 0, $role_id_3 = 0) {
	global $DB;

	if (($user_id == 0) || ($role_id_1 == 0)) { // Both mandatory
		return false;
	}

	$sql = 'SELECT ue.id'
		. ' FROM {user_enrolments} ue'
		. ' JOIN {enrol} e ON e.id = ue.enrolid'
		. ' JOIN {context} ct ON ct.instanceid = e.courseid'
		. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
		. ' JOIN {course} c ON c.id = e.courseid'
		. ' WHERE ue.userid = ?'
			. ' AND e.enrol = "manual"'
			. ' AND ct.contextlevel = 50'
			. ' AND ra.userid = ue.userid'
			. ' AND (ra.roleid = ? OR ra.roleid = ? OR ra.roleid = ?)'
			. ' AND c.idnumber = "SUBS_FORMS"';
	$db_ret = $DB->get_records_sql($sql, array($user_id, $role_id_1, $role_id_2, $role_id_3));
	if (empty($db_ret)) {
		return false;
	} else {
		return true;
	}
}

function write_form_settings($author, $form_data) {
	global $DB;

    $record = new stdClass();
    $record->formref = strtoupper($form_data->formref);
    $record->author = $author;
	$record->date = time();
    $record->name = $form_data->name;
	$record->description = $form_data->description['text'];
	$record->modular = $form_data->modular;
	$record->student = $form_data->student;
	$record->visible = $form_data->visible;
	$record->auth_1_role = $form_data->auth_1_role;
	$record->auth_1_notes = $form_data->auth_1_notes;
	$record->auth_2_role = $form_data->auth_2_role;
	$record->auth_2_notes = $form_data->auth_2_notes;
	$record->auth_3_role = $form_data->auth_3_role;
	$record->auth_3_notes = $form_data->auth_3_notes;
	$record->auth_4_role = $form_data->auth_4_role;
	$record->auth_4_notes = $form_data->auth_4_notes;
	$record->auth_5_role = $form_data->auth_5_role;
	$record->auth_5_notes = $form_data->auth_5_notes;
	$record->auth_6_role = $form_data->auth_6_role;
	$record->auth_6_notes = $form_data->auth_6_notes;

	$settings = read_form_settings_by_ref($record->formref);
	if ($settings !== false) {
		$id = $settings->id;
		$record->id = $id;
		$DB->update_record('local_obu_forms', $record);
	} else {
		$id = $DB->insert_record('local_obu_forms', $record);
	}

	return $id;
}

function read_form_settings($form_id) {
    global $DB;

	$settings = $DB->get_record('local_obu_forms', array('id' => $form_id), '*', MUST_EXIST);

	return $settings;
}

function read_form_settings_by_ref($formref) {
    global $DB;

	$settings = $DB->get_record('local_obu_forms', array('formref' => strtoupper($formref)), '*', IGNORE_MISSING);

	return $settings;
}

function get_forms($manager, $staff, $pg_student, $ump_student) {
    global $DB;

	if (!$manager && !$staff && !$pg_student && !$ump_student) { // Nothing for you here...
		return array();
	}

	// Firstly, get all the forms of the correct type
	$conditions = array();
	if (!$manager) {
		$conditions['visible'] = 1;
		if (!$pg_student && !$ump_student) { // Just staff forms
			$conditions['student'] = 0;
		} else {
			if ($pg_student && !$ump_student) { // Just PG forms
				$conditions['modular'] = 0;
			} else if (!$pg_student && $ump_student) { // Just UMP forms
				$conditions['modular'] = 1;
			}
			if (!$staff) { // Just student forms
				$conditions['student'] = 1;
			}
		}
	}
	$forms = $DB->get_records('local_obu_forms', $conditions);

	// Now, include and index those forms that have published templates
	$valid = array();
	$index = array();
	foreach ($forms as $form) {
		if ($manager || (get_form_template($form->id) !== false)) { // Only include unpublished forms for managers
			// If a forms manager, only include forms that they manage
			if (!$manager || is_manager($form)) {
				$valid[] = $form;
				$index[] = $form->formref;
			}
		}
	}

	// Sort the index and create an array of the valid forms
	natcasesort($index);
	$forms = array();
	foreach ($index as $key => $ref) {
		$forms[] = $valid[$key];
	}

	return $forms;
}

function get_forms_data($formref, $date_from, $date_to) {
    global $DB;

	// Get the selected form data records for each published template of this form type
	$time_to = $date_to + 86399; // Take up to midnight on the last day
	$forms_data = array();
	$settings = read_form_settings_by_ref($formref);
	$templates = read_form_templates($settings->id);
	foreach ($templates as $template) {
		if ($template->published) {
			$where = 'template_id = ' . $template->id . ' and date >= ' . $date_from . ' and date <= ' . $time_to;
			$records = $DB->get_records_select('local_obu_forms_data', $where);
			foreach ($records as $record) {
				$forms_data[] = $record;
			}
		}
	}

	return $forms_data;
}

function get_withdrawals($date_from, $date_to) {
    global $DB;

	$time_to = $date_to + 86399; // Take up to midnight on the last day

	// Get the required form data records for each published template of the withdrawal form types
	$sql = 'SELECT d.id AS form_id, f.formref AS form_ref, u.username AS student, u.lastname AS lastname, u.firstname AS firstname, d.auth_1_date AS authorised, d.data AS data'
		. ' FROM {local_obu_forms} f'
		. ' JOIN {local_obu_forms_templates} t ON t.form_id = f.id AND t.published = 1'
		. ' JOIN {local_obu_forms_data} d ON d.template_id = t.id AND d.authorisation_state = 2'
		. ' JOIN {user} u ON u.id = d.author'
		. ' WHERE f.formref LIKE "_20%"'
		. '  AND d.auth_1_date >= ' . $date_from
		. '  AND d.auth_1_date <= ' . $time_to
		. ' ORDER BY student, authorised';

	return $DB->get_records_sql($sql);
}

function write_form_template($author, $form_data) {
	global $DB;

	$settings = read_form_settings_by_ref($form_data->formref);
	if ($settings === false) {
		return 0;
	}

    $current_version = strtoupper($form_data->version);
    $new_version = strtoupper($form_data->new_version); // New version name (if any)

	$record = new stdClass();
	$record->form_id = $settings->id;
	if ($new_version == '') { // No new version name
		$record->version = $current_version;
	} else {
		$record->version = $new_version;
	}
    $record->author = $author;
	$record->date = time();
	$record->published = $form_data->published;
	$record->data = $form_data->data['text'];

	$template = read_form_template($record->form_id, $current_version);
	if ($template !== false) {
		$id = $template->id;
		$record->id = $id;
		$DB->update_record('local_obu_forms_templates', $record);
	} else {
		$id = $DB->insert_record('local_obu_forms_templates', $record);
	}

	return $id;
}

function read_form_templates($form_id) {
	global $DB;

	$templates = $DB->get_records('local_obu_forms_templates', array('form_id' => $form_id), 'version', '*');

	return $templates;
}

function read_form_template($form_id, $version) {
    global $DB;

	$template = $DB->get_record('local_obu_forms_templates', array('form_id' => $form_id, 'version' => strtoupper($version)), '*', IGNORE_MISSING);

	return $template;
}

function read_form_template_by_id($template_id) {
    global $DB;

	$template = $DB->get_record('local_obu_forms_templates', array('id' => $template_id), '*', MUST_EXIST);

	return $template;
}

function read_all_form_settings_with_template_id() {
    global $DB;

    $sql = "SELECT DISTINCT 
        t.id as 'template_id', 
        f.*
    FROM {local_obu_forms_templates} t 
    INNER JOIN {local_obu_forms} f ON t.form_id = f.id";

    return $DB->get_records_sql($sql);
}

function get_form_template($form_id, $include_unpublished = false) { // return the latest version of the template for the given form
    global $DB;

    // return the latest version
	$template = null;
	$templates = read_form_templates($form_id);
	foreach ($templates as $t) {
		if ($t->published || $include_unpublished) {
			$template = $t;
		}
	}

	if ($template) {
		return $template;
	}
	return false;
}

function write_form_data($record) {
    global $DB;

	if ($record->id == 0) {
		$id = $DB->insert_record('local_obu_forms_data', $record);
	} else {
		$id = $record->id;
		$form = $DB->get_record('local_obu_forms_data', array('id' => $id), '*', MUST_EXIST);
		$DB->update_record('local_obu_forms_data', $record);
	}

	return $id;
}

function read_form_data($data_id, &$record) {
    global $DB;

	$record = $DB->get_record('local_obu_forms_data', array('id' => $data_id), '*');
	if (!$record) {
		return false;
	}

	return true;
}

function get_form_data($user_id = 0) {
    global $DB;

	$conditions = array();
	if ($user_id > 0) {
		$conditions['author'] = $user_id;
	}
	$data = $DB->get_records('local_obu_forms_data', $conditions, 'date DESC');

	return $data;
}

function write_form_auths($record) {
    global $DB;

	if ($record->id == 0) {
		$id = $DB->insert_record('local_obu_forms_auths', $record);
	} else {
		$id = $record->id;
		$DB->update_record('local_obu_forms_auths', $record);
	}

	return $id;
}

function read_form_auths($data_id, &$record) {
    global $DB;

	$record = $DB->get_record('local_obu_forms_auths', array('data_id' => $data_id), '*', IGNORE_MISSING);
	if ($record === false) {
		$record = new stdClass();
		$record->id = 0;
		$record->data_id = $data_id;
		$record->authoriser = 0;
		$record->date = 0;
	}
}

function delete_form_auths($record) {
    global $DB;

	if ($record->id != 0) {
		$DB->delete_records('local_obu_forms_auths', array('id' => $record->id));
	}
}

function get_form_auths($authoriser) {
    global $DB;

	$conditions = array();
	if ($authoriser) {
		$conditions['authoriser'] = $authoriser;
	}
	$auths = $DB->get_records('local_obu_forms_auths', $conditions, 'request_date');

	return $auths;
}

function write_form_forwarder($from_id, $to_id, $start_date, $stop_date) {
    global $DB, $USER;

	$record = read_form_forwarder($from_id);
	$record->to_id = $to_id;
	$record->start_date = $start_date;
	$record->stop_date = $stop_date;
	$record->updater_id = $USER->id;
	$record->update_date = time();

	if ($record->id == 0) {
		$DB->insert_record('local_obu_forms_forwarders', $record);
	} else {
		$DB->update_record('local_obu_forms_forwarders', $record);
	}

	return;
}

function read_form_forwarder($from_id) {
    global $DB;

	$record = $DB->get_record('local_obu_forms_forwarders', array('from_id' => $from_id), '*', IGNORE_MISSING);
	if ($record === false) {
		$record = new stdClass();
		$record->id = 0;
		$record->from_id = $from_id;
		$record->to_id = 0;
		$record->start_date = 0;
		$record->stop_date = 0;
	}

	return $record;
}

function delete_form_forwarder($from_id) {
    global $DB;

	$record = read_form_forwarder($from_id);
	if ($record->id != 0) {
		$DB->delete_records('local_obu_forms_forwarders', array('id' => $record->id));
	}

	return;
}

function get_form_forwarders() {
    global $DB;

	return $DB->get_records('local_obu_forms_forwarders');
}

function get_academic_adviser($user_id) {
	global $DB;

	// Get any Academic Advisers for the user
	$adviser = array();
	$sql = 'SELECT u.id'
		. ' FROM {user_enrolments} ue'
		. ' JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = "database"'
		. ' JOIN {context} ct ON ct.instanceid = e.courseid AND ct.contextlevel = 50'
		. ' JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.contextid = ct.id AND ra.roleid = 5'
		. ' JOIN {course} c ON c.id = e.courseid AND c.idnumber LIKE "%$virtual_office"'
		. ' JOIN {user} u ON u.username = SUBSTRING(c.idnumber, 1, 8)'
		. ' WHERE ue.userid = ?';
	$db_ret = $DB->get_records_sql($sql, array($user_id));
	foreach ($db_ret as $rec) {
		$adviser[] = $rec->id;
	}

	if (empty($adviser)) { // Shouldn't happen, of course
		return 0;
	}

	return $adviser[0]; // We assume only one (or that the first is most relevant)
}

function get_advisers($modular, $user_id) {
	global $DB;

	$adviser = array();
	$adviser[0] = get_string('select', 'local_obu_forms'); // The 'Please select' default

	// Get any Academic Adviser for the user
	$adviser_id = get_academic_adviser($user_id);
	if ($adviser_id != 0) {
		$user = get_complete_user_data('id', $adviser_id);
		$adviser[$user->id] = $user->firstname . ' ' . $user->lastname;
	}

	// Get any Student Support Coordinators/Subject Co-ordinators/Programme Leads/Programme Administrators for the user's course
	$courses = get_current_courses($modular, $user_id); // Should only be one
	$course_id = key($courses);
	if ($course_id) {
		$context = context_course::instance($course_id);
		$role = $DB->get_record('role', array('shortname' => 'ssc'), 'id', MUST_EXIST); // Student Support Coordinator
		$advisers = get_role_users($role->id, $context, true, 'u.id'); // Include inherited roles
		foreach ($advisers as $a) {
			$user = get_complete_user_data('id', $a->id);
			$adviser[$a->id] = $user->firstname . ' ' . $user->lastname;
		}
		$role = $DB->get_record('role', array('shortname' => 'subject_coordinator'), 'id', MUST_EXIST); // Subject Co-ordinator
		$advisers = get_role_users($role->id, $context, true, 'u.id'); // Include inherited roles
		foreach ($advisers as $a) {
			$user = get_complete_user_data('id', $a->id);
			$adviser[$a->id] = $user->firstname . ' ' . $user->lastname;
		}
		$role = $DB->get_record('role', array('shortname' => 'programme_lead'), 'id', MUST_EXIST); // Programme Lead
		$advisers = get_role_users($role->id, $context, true, 'u.id'); // Include inherited roles
		foreach ($advisers as $a) {
			$user = get_complete_user_data('id', $a->id);
			$adviser[$a->id] = $user->firstname . ' ' . $user->lastname;
		}
		$role = $DB->get_record('role', array('shortname' => 'programme_admin'), 'id', MUST_EXIST); // Programme Administrator
		$advisers = get_role_users($role->id, $context, true, 'u.id'); // Include inherited roles
		foreach ($advisers as $a) {
			$user = get_complete_user_data('id', $a->id);
			$adviser[$a->id] = $user->firstname . ' ' . $user->lastname;
		}
	}
	$adviser[] = get_string('no_one', 'local_obu_forms'); // The 'I spoke to no-one' selection

	return $adviser;
}

function get_supervisors($user_id) { // In this iterration, at least, a supervisor can be any member of staff!
	global $DB;

	$supervisor = array();
	$supervisor[0] = get_string('select', 'local_obu_forms'); // The 'Please select' default

	$sql = 'SELECT u.id, u.username, u.firstname, u.lastname'
		. ' FROM {user} u'
		. ' WHERE u.username REGEXP "^p[0-9]+$"'
		. ' AND u.deleted = 0'
		. ' AND u.suspended = 0'
		. ' ORDER BY u.lastname, u.firstname';
	$db_ret = $DB->get_records_sql($sql);
	foreach ($db_ret as $user) {
		$supervisor[$user->id] = $user->lastname . ', ' . $user->firstname . ' (' . $user->username . ')';
	}

	return $supervisor;
}

function get_campuses() {
	global $DB;

	$campus = array();

	$sql = 'SELECT DISTINCT SUBSTRING(idnumber, 1, LOCATE("~", idnumber) - 1) AS campus'
		. ' FROM {course}'
		. ' WHERE idnumber LIKE "%#%"'
		. ' ORDER BY campus';

	$db_ret = $DB->get_records_sql($sql);
	foreach ($db_ret as $rec) {
		$campus[$rec->campus] = $rec->campus;
	}

	return $campus;
}

function get_authoriser($author_id, $modular, $role, $fields) {
	global $DB;

	// Determine if the student is the author or the subject
	if (!$fields['student_number']) {
		$student_id = $author_id;
	} else {
		$student = get_complete_user_data('username', $fields['student_number']);
		$student_id = $student->id;
	}

	$authoriser_id = 0;
	if ($role == 1) { // CSA/SC
		$authoriser = get_complete_user_data('username', 'csa');
		$authoriser_id = $authoriser->id;
	} else if ($role == 2) { // Module Leader
		if (!$fields['campus']) {
			$campus = 'OBO'; // The default
		} else {
			$campus = strtoupper($fields['campus']);
		}
		if ($fields['module']) {
			$module = strtoupper($fields['module']);
		} else if ($fields['free_language']) {
			$module = $fields['free_language'];
			$pos = strpos($module, ': ');
			if (Spos !== false) {
				$module = substr($module, 0, $pos);
			}
		} else {
			$module = '';
		}
		if ($module != '') {
			$modules = get_current_modules();
			$module_id = array_search($module . ' [' . $campus . ']', $modules, true);
			if ($module_id > 0) {
				$authoriser_id = get_module_leader($module_id);
			}
		}
	} else if ($role == 3) { // Subject Coordinator
		if ($fields['course']) { // Might not be present (or might not be mandatory)
			$course_code = strtoupper($fields['course']);
			if (strpos($course_code, '[') === false) {
				$course_code = $course_code . '[OBO]'; // Default campus
			}
			$courses = get_current_courses($modular);
			$course_id = array_search($course_code, $courses, true);
		} else { // Get the student's current course (programme)
			$courses = get_current_courses($modular, $student_id);
			$course_id = key($courses);
		}
		if ($course_id) {
			$context = context_course::instance($course_id);
			$sc_role = $DB->get_record('role', array('shortname' => 'subject_coordinator'), 'id', MUST_EXIST);
			$subject_coordinators = get_role_users($sc_role->id, $context, false, 'u.id'); // Exclude inherited roles
			foreach ($subject_coordinators as $subject_coordinator) {
				$authoriser_id = $subject_coordinator->id;
			}
		}
	} else if (($role == 4) && $fields['supervisor']) { // Supervisor (field must be present)
		$start_pos = strpos($fields['supervisor'], '(') + 1;
		$end_pos = strpos($fields['supervisor'], ')', $start_pos);
		$supervisor = $DB->get_record('user', array('username' => substr($fields['supervisor'], $start_pos, ($end_pos - $start_pos))), 'id', MUST_EXIST);
		$authoriser_id = $supervisor->id;
	} else if ($role == 5) { // Academic Adviser
		$context = context_user::instance($student_id);
		$aa_role = $DB->get_record('role', array('shortname' => 'academic_adviser'), 'id', MUST_EXIST);
		$academic_advisers = get_role_users($aa_role->id, $context, false, 'u.id'); // Exclude inherited roles
		foreach ($academic_advisers as $academic_adviser) {
			$authoriser_id = $academic_adviser->id;
		}
	} else if ($role == 6) { // Programme Lead
		$authoriser_id = get_programme_leads($student_id, $modular, 0);
	} else if ($role == 7) { // Programme Lead (Joint Honours) - only present for joint honours students (will skip step otherwise)
		$authoriser_id = get_programme_leads($student_id, $modular, 1);
	} else if (($role == 8) && $fields['module_2']) { // Module Leader (2) - second module must be present (will skip step otherwise)
		if (!$fields['campus_2']) {
			$campus = 'OBO'; // The default
		} else {
			$campus = $fields['campus_2'];
		}
		$modules = get_current_modules();
		$module_id = array_search(strtoupper($fields['module_2'] . ' [' . $campus . ']'), $modules, true);
		$authoriser_id = get_module_leader($module_id);
	} else if ($role == 9) { // Exchanges Office
		$authoriser = get_complete_user_data('username', 'exchanges');
		$authoriser_id = $authoriser->id;
	} else if ($role == 10) { // Student
		$authoriser_id = $student->id;
	} else if (($role == 11) && $fields['supervisor_2']) { // Supervisor (2) - second supervisor must be present (will skip step otherwise)
		$start_pos = strpos($fields['supervisor_2'], '(') + 1;
		$end_pos = strpos($fields['supervisor_2'], ')', $start_pos);
		$supervisor = $DB->get_record('user', array('username' => substr($fields['supervisor_2'], $start_pos, ($end_pos - $start_pos))), 'id', MUST_EXIST);
		$authoriser_id = $supervisor->id;
	} else if ($role == 12) { // Admissions
		$authoriser = get_complete_user_data('username', 'admissions');
		$authoriser_id = $authoriser->id;
	} else if ($role == 13) { // ISA Team
		$authoriser = get_complete_user_data('username', 'isat');
		$authoriser_id = $authoriser->id;
	} else if (($role == 14) && $fields['course_change']) { // Programme Lead (Course Change)
		$course_id = get_course_id($fields['course_change'], $modular);
		$authoriser_id = get_programme_lead($course_id);
	} else if (($role == 15) && $fields['course_change']) { // Subject Coordinator (Course Change)
		$course_id = get_course_id($fields['course_change'], $modular);
		if ($course_id) {
			$context = context_course::instance($course_id);
			$sc_role = $DB->get_record('role', array('shortname' => 'subject_coordinator'), 'id', MUST_EXIST);
			$subject_coordinators = get_role_users($sc_role->id, $context, false, 'u.id'); // Exclude inherited roles
			foreach ($subject_coordinators as $subject_coordinator) {
				$authoriser_id = $subject_coordinator->id;
			}
		}
	} else if (($role == 16) && $fields['course_change_joint']) { // Subject Coordinator (Course Change, Joint Honours)
		$course_id = get_course_id($fields['course_change_joint'], $modular);
		if ($course_id) {
			$context = context_course::instance($course_id);
			$sc_role = $DB->get_record('role', array('shortname' => 'subject_coordinator'), 'id', MUST_EXIST);
			$subject_coordinators = get_role_users($sc_role->id, $context, false, 'u.id'); // Exclude inherited roles
			foreach ($subject_coordinators as $subject_coordinator) {
				$authoriser_id = $subject_coordinator->id;
			}
		}
	}

	if (($authoriser_id == 0) && ($role != 7) && ($role != 8) && ($role != 11) && ($role != 16)) { // Don't leave them hanging...
		$authoriser = get_complete_user_data('username', 'csa-tbd'); // Default ('TO BE DETERMINED')
		$authoriser_id = $authoriser->id;
	}

	// Finally, check if all forms for the determined authoriser should be forwarded (temporarily)
	$forwarder = read_form_forwarder($authoriser_id);
	if ($forwarder->id != 0) { // There is a forwarder - action it if it's active today
		$today = strtotime('today midnight');
		if (($today >= $forwarder->start_date) && ($today <= $forwarder->stop_date)) {
			$authoriser_id = $forwarder->to_id;
		}
	}

	return $authoriser_id;
}

function get_course_id($course, $modular) { // Course could just be the course code or the title with the code in round brackets
	$last_bracket = -1;
	while (($pos = strpos($course, '(', ($last_bracket + 1))) !== false) {
		$last_bracket = $pos;
	}
	if (($last_bracket > -1) && (($pos = strpos($course, ')', ($last_bracket + 1))) !== false)) {
		$course_code = substr($course, ($last_bracket + 1), ($pos - ($last_bracket + 1)));
	} else if (strpos($course, '[') === false) {
		$course_code = $course . '[OBO]'; // Default campus
	} else {
		$course_code = $course;
	}
	$courses = get_current_courses($modular);

	return(array_search(strtoupper($course_code), $courses, true));
}

// Check if the given user is a member of staff
function is_staff($username = null) {
	$is_staff = ((strlen($username) == 8) && ((substr($username, 0, 1) == 'p') || (substr($username, 0, 1) == 'd')) && is_numeric(substr($username, 1)));

	return $is_staff;
}

// Check 'quickly' if the user is officially enrolled as a student on any course
function is_student($user_id = 0, $type = null) {
	global $DB;

	if ($user_id == 0) { // Mandatory
		return false;
	}

	$role = $DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST);
	$sql = 'SELECT c.id'
		. ' FROM {user_enrolments} ue'
		. ' JOIN {enrol} e ON e.id = ue.enrolid'
		. ' JOIN {context} ct ON ct.instanceid = e.courseid'
		. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
		. ' JOIN {course} c ON c.id = e.courseid'
		. ' WHERE ue.userid = ?'
			. ' AND e.enrol = "database"'
			. ' AND ct.contextlevel = 50'
			. ' AND ra.userid = ue.userid'
			. ' AND ra.roleid = ?'
			. ' AND c.idnumber LIKE "%#%"';
	if ($type == 'UMP') { // Restrict the courses to a given level
		$sql .= ' AND c.idnumber LIKE "%~UG%"';
	} else if ($type == 'PG') {
		$sql .= ' AND c.idnumber LIKE "%~PG%"';
	}
	$db_ret = $DB->get_records_sql($sql, array($user_id, $role->id));
	if (empty($db_ret)) {
		return false;
	} else {
		return true;
	}
}

function get_current_courses($modular = false, $user_id = 0, $names = false, $joint = false) {
	global $DB;

	$courses = array();
	if ($user_id == 0) { // Just need all the course codes or names (for input/validation purposes)
		$sql = 'SELECT c.id, c.shortname, c.idnumber, c.fullname FROM {course} c WHERE c.idnumber LIKE "%#%"';
		$db_ret = $DB->get_records_sql($sql, array());
		foreach ($db_ret as $row) {

			// Restrict the courses to a given type?
			$handler = core_course\customfield\course_handler::create();
			$custom_fields = $handler->export_instance_data_object($row->id, true);
			if (($modular !== false) && (($modular && ($custom_fields->modular_course != 'Yes')) || (!$modular && ($custom_fields->modular_course == 'Yes')))) {
				continue;
			}
			if ($joint && ($custom_fields->joint_course != 'Yes')) {
				continue;
			}

			if (!$names) {
				$courses[$row->id] = $row->shortname;
			} else {
				$courses[$row->id] = $row->fullname . '  (' . $row->shortname . ')';
			}
		}
		asort($courses);
	} else { // Need the full names of the course(s) on which this user is enrolled as a student
		$role = $DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST);
		$sql = 'SELECT c.id, c.fullname'
			. ' FROM {user_enrolments} ue'
			. ' JOIN {enrol} e ON e.id = ue.enrolid'
			. ' JOIN {context} ct ON ct.instanceid = e.courseid'
			. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
			. ' JOIN {course} c ON c.id = e.courseid'
			. ' WHERE ue.userid = ?'
				. ' AND e.enrol = "database"'
				. ' AND ct.contextlevel = 50'
				. ' AND ra.userid = ue.userid'
				. ' AND ra.roleid = ?'
				. ' AND c.idnumber LIKE "%#%"'
				. ' ORDER BY c.fullname';
		$db_ret = $DB->get_records_sql($sql, array($user_id, $role->id));
		foreach ($db_ret as $row) {

			// Restrict the courses to a given type?
			$handler = core_course\customfield\course_handler::create();
			$custom_fields = $handler->export_instance_data_object($row->id, true);
			if (($modular !== false) && (($modular && ($custom_fields->modular_course != 'Yes')) || (!$modular && ($custom_fields->modular_course == 'Yes')))) {
				continue;
			}

			$courses[$row->id] = $row->fullname;
		}
	}

	return $courses;
}

function get_current_course_id_number($modular = false, $user_id = 0, $names = false, $joint = false){
    global $DB;
    $courses = array();

    $role = $DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST);
    $sql = 'SELECT c.id, c.idnumber'
        . ' FROM {user_enrolments} ue'
        . ' JOIN {enrol} e ON e.id = ue.enrolid'
        . ' JOIN {context} ct ON ct.instanceid = e.courseid'
        . ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
        . ' JOIN {course} c ON c.id = e.courseid'
        . ' WHERE ue.userid = ?'
        . ' AND e.enrol = "database"'
        . ' AND ct.contextlevel = 50'
        . ' AND ra.userid = ue.userid'
        . ' AND ra.roleid = ?'
        . ' AND c.idnumber LIKE "%#%"'
        . ' ORDER BY c.fullname';
    $db_ret = $DB->get_records_sql($sql, array($user_id, $role->id));
    foreach ($db_ret as $row) {

        // Restrict the courses to a given type?
        $handler = core_course\customfield\course_handler::create();
        $custom_fields = $handler->export_instance_data_object($row->id, true);
        if (($modular !== false) && (($modular && ($custom_fields->modular_course != 'Yes')) || (!$modular && ($custom_fields->modular_course == 'Yes')))) {
            continue;
        }

        $courses[$row->id] = $row->idnumber;
    }
    return $courses;
}

function get_current_modules($category_id = 0, $type = null, $user_id = 0, $enroled = true, $free_language = false) {
	global $DB;

	// Establish the initial selection criteria to apply
	$criteria = 'c.idnumber LIKE "%.%" AND (substr(c.shortname, 8, 2) = " (" OR substr(c.shortname, 9, 2) = " (")';
	if ($category_id > 0) {
		// Restrict modules to ones in the given category
		$criteria = $criteria . ' AND c.category = ' . $params['category_id'];
	}
	if ($user_id > 0) {
		// Restrict modules to ones in which this user is either enroled or not enroled
		if ($enroled) {
			$criteria = $criteria . ' AND ue.userid = ' . $user_id;
		} else {
			$criteria = $criteria . ' AND ue.userid != ' . $user_id;
		}
		$sql = 'SELECT ue.id, c.id AS course_id, c.fullname, c.shortname, c.enddate '
			. 'FROM {course} c '
			. 'JOIN {enrol} e ON e.courseid = c.id '
			. 'JOIN {user_enrolments} ue ON ue.enrolid = e.id '
			. 'WHERE ' . $criteria . ' '
			. 'ORDER BY c.shortname';
	} else {
		$sql = 'SELECT c.id AS course_id, c.fullname, c.shortname, c.enddate '
			. 'FROM {course} c '
			. 'WHERE ' . $criteria . ' '
			. 'ORDER BY c.shortname';
	}

	// Read the course (module) records that match our chosen criteria
	$db_ret = $DB->get_records_sql($sql, array());

	// Create an array of the current modules with the required type (if given)
	$modules = array();
	if ($user_id) {
		$modules[0] = ''; // The 'None' option
	}
	$this_month = date('Ym');
	foreach ($db_ret as $row) {
		$pos = strpos($row->shortname, ' '); //  We have 7 or 8 character module codes
		if (substr($row->shortname, ($pos - 4), 1) < '7') {
			$module_type = 'U';
		} else {
			$module_type = 'P';
		}
		if ((!$type || ($module_type == $type)) && ($this_month <= date('Ym', $row->enddate))) { // Must be the required type and not already ended

			// Restrict to free language modules only?
			if ($free_language) {
				$handler = core_course\customfield\course_handler::create();
				$custom_fields = $handler->export_instance_data_object($row->course_id, true);
				if ($custom_fields->free_language_module != 'Yes') {
					continue;
				}
			}

			if (!$free_language && ($user_id == 0)) { // Just need the module codes and associated campus codes for validation purposes
				$module_code = substr($row->shortname, 0, $pos);
				$campus_code = 'OBO'; // The default
				$pos = strpos($row->fullname, '[');
				if ($pos !== false) {
					$tail = substr($row->fullname, $pos + 1);
					$pos = strpos($tail, '])');
					if ($pos !== false) {
						$campus_code = substr($tail, 0, $pos);
					}
				}
				$module = $module_code . ' [' . $campus_code . ']';
			} else { // Need the full name
				$pos = strpos($row->fullname, ' (');
				if ($pos !== false) {
					$module = substr($row->fullname, 0, $pos);
				} else {
					$module = $row->fullname;
				}
			}

			if (!in_array($module, $modules, true)) {
				$modules[$row->course_id] = $module;
			}
		}
	}

	return $modules;
}

function get_programme_leads($student_id = 0, $modular = false, $index = 0) {
	global $DB;

	// Get all courses for this student (normally 1 but 2 for joint honours students)
	$courses = get_current_courses($modular, $student_id);
	if (empty($courses)) {
		return 0;
	}

	$programme_leads = array();
	foreach ($courses as $course_id => $course_name) {
		$programme_lead = get_programme_lead($course_id);
		if (($programme_lead > 0) && !in_array($programme_lead, $programme_leads, true)) {
			$programme_leads[] = $programme_lead;
		}
	}

	if (empty($programme_leads[$index])) {
		return 0;
	}

	return $programme_leads[$index];
}

function get_programme_lead($course_id = 0) {
	global $DB;

	$context = context_course::instance($course_id);
	if ($context == null) {
		return 0;
	}

	// Get all the users enrolled on the course (with their enrollment methods) that have the Programme Lead role
	$sql = 'SELECT ue.userid, e.enrol'
		. ' FROM {enrol} e'
		. ' JOIN {user_enrolments} ue ON ue.enrolid = e.id'
		. ' JOIN {role_assignments} ra ON ra.userid = ue.userid'
		. ' JOIN {role} r ON r.id = ra.roleid'
		. ' WHERE e.courseid = ? AND ra.contextid = ? AND r.shortname = "programme_lead"'
		. ' ORDER BY ue.timecreated';
	$db_ret = $DB->get_records_sql($sql, array($course_id, $context->id));

	// Find the latest PL enrollment (giving precedence to external ones)
	$programme_lead = 0;
	$external = false;
	foreach ($db_ret as $row) {
		if ($row->enrol == 'database') {
			$programme_lead = $row->userid;
			$external = true;
		} else if (!$external) {
			$programme_lead = $row->userid;
		}
	}

	return $programme_lead;
}

function get_module_leader($module_id = 0) {
	global $DB;

	// Validate the module ID
	if ($module_id == 0) {
		return 0;
	}
	$context = context_course::instance($module_id);
	if ($context == null) {
		return 0;
	}

	// Get all the users enrolled on the module (with their enrollment methods) that have the Module Leader role
	$sql = 'SELECT DISTINCT ue.id, ue.userid, e.enrol'
		. ' FROM {enrol} e'
		. ' JOIN {user_enrolments} ue ON ue.enrolid = e.id'
		. ' JOIN {role_assignments} ra ON ra.userid = ue.userid'
		. ' JOIN {role} r ON r.id = ra.roleid'
		. ' WHERE e.courseid = ? AND ra.contextid = ? AND r.shortname = "course_leader"'
		. ' ORDER BY ue.timecreated';
	$db_ret = $DB->get_records_sql($sql, array($module_id, $context->id));

	// Find the latest ML enrollment (giving precedence to 'external database' ones)
	$module_leader = 0;
	$external_database = false;
	foreach ($db_ret as $row) {
		if ($row->enrol == 'database') {
			$module_leader = $row->userid;
			$external_database = true;
		} else if (!$external_database) {
			$module_leader = $row->userid;
		}
	}

	return $module_leader;
}
