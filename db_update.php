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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
function write_form_settings($author, $form_data) {
	global $DB;
	
    $record = new stdClass();
    $record->formref = strtoupper($form_data->formref);
    $record->author = $author;
	$record->date = time();
    $record->name = $form_data->name;
	$record->description = $form_data->description['text'];
	$record->student = $form_data->student;
	$record->visible = $form_data->visible;
	$record->auth_1_role = $form_data->auth_1_role;
	$record->auth_1_notes = $form_data->auth_1_notes;
	$record->auth_2_role = $form_data->auth_2_role;
	$record->auth_2_notes = $form_data->auth_2_notes;
	$record->auth_3_role = $form_data->auth_3_role;
	$record->auth_3_notes = $form_data->auth_3_notes;

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

function get_forms($manager, $staff, $student) {
    global $DB;
	
	if (!$manager && !$staff && !$student) { // Nothing for you here...
		return array();
	}
	
	// Firstly, get all the forms of the correct type
	$conditions = array();
	if (!$manager) {
		$conditions['visible'] = 1;
		if (!$student) { // Just staff forms
			$conditions['student'] = 0;
		} else if (!$staff) { // Just student forms
			$conditions['student'] = 1;
		}			
	}
	$forms = $DB->get_records('local_obu_forms', $conditions);
	
	// Now, include and index those forms that have published templates
	$valid = array();
	$index = array();
	foreach ($forms as $form) {
		if ($manager || (get_form_template($form->id) !== false)) {
			$valid[] = $form;
			$index[] = $form->formref;
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

function write_form_template($author, $form_data) {
	global $DB;
	
	$settings = read_form_settings_by_ref($form_data->formref);
	if ($settings === false) {
		return 0;
	}

    $record = new stdClass();
	$record->form_id = $settings->id;
    $record->version = strtoupper($form_data->version);
    $record->author = $author;
	$record->date = time();
	$record->published = $form_data->published;
	$record->data = $form_data->data['text'];

	$template = read_form_template($record->form_id, $record->version);
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

function get_form_data($user_id) {
    global $DB;
	
	$conditions = array();
	$conditions['author'] = $user_id;
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

function get_advisers($user_id) {
	global $DB;
	   
	$context = context_user::instance($user_id);
	$role = $DB->get_record('role', array('shortname' => 'academic_adviser'), 'id', MUST_EXIST);
	$advisers = get_role_users($role->id, $context, false, 'u.id');
	$adviser = array();
	foreach ($advisers as $a) {
		$user = get_complete_user_data('id', $a->id);
		$adviser[] = $user->firstname . ' ' . $user->lastname;
	}
	
	$adviser[] = 'Mandy Archer';
	$adviser[] = 'Robert Drake';
	$adviser[] = 'Emma Fisher';
	$adviser[] = 'Catherine Foley';
	$adviser[] = 'Laura Hart';
	$adviser[] = 'Linda Heap';
	$adviser[] = 'Michelle Hicks';
	$adviser[] = 'David Howell';
	$adviser[] = 'Michelle Jacobs';
	$adviser[] = 'Martina Kaupp-Roberts';
	$adviser[] = 'Lucinda Lyon';
	$adviser[] = 'Catherine Ridley-Hughes';
	$adviser[] = 'Fareena Salih';
	$adviser[] = 'Marta Solsona';
	$adviser[] = 'Chris Taylor';
	$adviser[] = 'Sam Varney';
	$adviser[] = 'Mel Williamson';
		
	return $adviser;
}
	
function get_authoriser($author_id, $role, $fields) {
	global $DB;
	
	$authoriser_id = 0;
	if ($role == 1) { // CSA
		$authoriser = get_complete_user_data('username', 'csa');
		$authoriser_id = $authoriser->id;
	} else if ($role == 2) { // Module Leader
		$modules = get_current_modules();
		$module_id = array_search(strtoupper($fields['module']), $modules, true);
		if ($module_id) {
			$context = context_course::instance($module_id);
			$ml_role = $DB->get_record('role', array('shortname' => 'course_leader'), 'id', MUST_EXIST);
			$module_leaders = get_role_users($ml_role->id, $context, false, 'u.id');
			foreach ($module_leaders as $module_leader) {
				$authoriser_id = $module_leader->id;
			}
			$authoriser = get_complete_user_data('username', 'atutor');
			$authoriser_id = $authoriser->id;
		}
	} else if ($role == 3) { // Subject Coordinator
		if ($fields['course']) { // Might not be present (or might not be mandatory)
			$courses = get_current_courses();
			$course_id = array_search(strtoupper($fields['course']), $courses, true);
		} else { // Get the author's current course (programme)
			$courses = get_current_courses('P', $author_id);
			$course_id = key($courses);
		}
		if ($course_id) {
			$context = context_course::instance($course_id);
			$sc_role = $DB->get_record('role', array('shortname' => 'subject_coordinator'), 'id', MUST_EXIST);
			$subject_coordinators = get_role_users($sc_role->id, $context, false, 'u.id');
			foreach ($subject_coordinators as $subject_coordinator) {
				$authoriser_id = $subject_coordinator->id;
			}
			$authoriser = get_complete_user_data('username', 'atutor');
			$authoriser_id = $authoriser->id;
		}
	}
	
	if ($authoriser_id == 0) { // Don't leave them hanging...
		$authoriser = get_complete_user_data('username', 'csa'); // Default
		$authoriser_id = $authoriser->id;
	}
	
	return $authoriser_id;
}

function get_current_courses($type = null, $user_id = 0, $enrolled = true) {
	global $DB;
	
	// Establish the initial selection criteria to apply
	$criteria = 'c.visible = 1';
	if ($user_id > 0) {
		// Restrict courses to ones in which this user is either enrolled or not enrolled
		if ($enrolled) {
			$criteria = $criteria . ' AND ue.userid = ' . $user_id;
		} else {
			$criteria = $criteria . ' AND ue.userid != ' . $user_id;
		}
	}
	
	// Read the course records that match our chosen criteria
	$sql = 'SELECT c.id, c.idnumber, c.fullname '
		. 'FROM {course} c '
		. 'JOIN {enrol} e ON e.courseid = c.id '
		. 'JOIN {user_enrolments} ue ON ue.enrolid = e.id '
		. 'WHERE ' . $criteria . ' '
		. 'ORDER BY c.fullname';
	$db_ret = $DB->get_records_sql($sql, array());
	
	// Create an array of the current courses (programmes) with the required type (if given)
	$courses = array();
	foreach ($db_ret as $row) {
		$prefix = substr($row->idnumber, 0, 2);
		if (($type != 'P') || ($prefix == 'P~')) {
			if ($user_id == 0) { // Just need the course code for validation purposes
				$courses[$row->id] = $row->idnumber;
			} else { // Need the full name
				$courses[$row->id] = $row->fullname;
			}
		}
	}

	return $courses;
}

function get_current_modules($category_id = 0, $type = null, $user_id = 0, $enroled = true) {
	global $DB;
	
	// Establish the initial selection criteria to apply
	$criteria = 'c.visible = 1 AND substr(c.shortname, 7, 1) = " " AND substr(c.shortname, 13, 1) = "-" AND length(c.shortname) >= 18';
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
	}
	
	// Read the course (module) records that match our chosen criteria
	$sql = 'SELECT c.id, c.fullname, c.shortname '
		. 'FROM {course} c '
		. 'JOIN {enrol} e ON e.courseid = c.id '
		. 'JOIN {user_enrolments} ue ON ue.enrolid = e.id '
		. 'WHERE ' . $criteria;
	$db_ret = $DB->get_records_sql($sql, array());
	
	// Create an array of the current modules with the required type (if given)
	$modules = array();
	if ($user_id) {
		$modules[0] = ''; // The 'None' option
	}
	$now = time();
	foreach ($db_ret as $row) {
		$module_type = substr($row->fullname, 0, 1);
		$module_start = strtotime('01 ' . substr($row->shortname, 7, 3) . ' ' . substr($row->shortname, 10, 2));
		$module_end = strtotime('31 ' .	substr($row->shortname, 13, 3) . ' ' . substr($row->shortname, 16, 2));
		if ((!$type || ($module_type == $type)) && ($module_start <= $now) && ($module_end >= $now)) {
			if ($user_id == 0) { // Just need the module code for validation purposes
				$split_pos = strpos($row->fullname, ': ');
				if ($split_pos !== false) {
					$modules[$row->id] = substr($row->fullname, 0, $split_pos);
				}
			} else { // Need the full name
				$split_pos = strpos($row->fullname, ' (');
				if ($split_pos !== false) {
					$modules[$row->id] = substr($row->fullname, 0, $split_pos);
				} else {
					$modules[$row->id] = $row->fullname;
				}
			}
		}
	}

	return $modules;
}

function get_module_leader($course_id) {
	global $DB;
	
	// NOT USED
	$sql = 'SELECT u.id
		FROM {role_assignments} ra
		JOIN {user} u ON u.id = ra.userid
		JOIN {context} ctx ON ra.contextid = ctx.id
		LEFT JOIN (
			SELECT ue.id, ue.userid
			FROM {user_enrolments} ue
			LEFT JOIN {enrol} e ON e.id=ue.enrolid
			WHERE e.courseid = $course_id
		) ue ON ue.userid=u.id
		WHERE ctx.id $ctxcondition AND ue.id IS NULL';
		
	$db_ret = $DB->get_records_sql($sql, array());
}
