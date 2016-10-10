<?php

// This file is part of Moodle - http://moodle.org/
//
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
 * OBU Forms - Provide left hand navigation links
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/obu_forms/db_update.php');

function local_obu_forms_extend_navigation($navigation) {
    global $CFG, $USER, $PAGE;
	
	if (!isloggedin() || isguestuser()) {
		return;
	}
	
	$context = context_system::instance();
	$staff_manager = (has_capability('local/obu_forms:manage_pg', $context) || has_capability('local/obu_forms:manage_ump_staff', $context));
	$students_manager = (has_capability('local/obu_forms:manage_pg', $context) || has_capability('local/obu_forms:manage_ump_students', $context));
	$manager = ($staff_manager || $students_manager);
	$staff = is_staff($USER->username); // Has a 'p' number?
	$student = is_student($USER->id); // Enrolled on a PIP-based course (programme)?
	
	// Add the 'My Forms' option
	if ($manager || $staff || $student || !empty(get_form_data($USER->id))) {
		// Find the 'myprofile' node
		$nodeParent = $navigation->find('myprofile', navigation_node::TYPE_UNKNOWN);

		// Add the option to list their completed forms
		if ($nodeParent) {
			$node = $nodeParent->add(get_string('myforms', 'local_obu_forms'), '/local/obu_forms/index.php?userid=' . $USER->id);
		}
	}
	
	if (!$manager && !$staff && !$student) { // Move on now please, nothing more to see here...
		return;
	}
	 
	// Find the 'forms' node
	$nodeParent = $navigation->find(get_string('forms', 'local_obu_forms'), navigation_node::TYPE_SYSTEM);
	
	// If necessary, add the 'forms' node to 'home'
	if (!$nodeParent) {
		$nodeHome = $navigation->children->get('1')->parent;
		if ($nodeHome) {
			$nodeParent = $nodeHome->add(get_string('forms', 'local_obu_forms'), null, navigation_node::TYPE_SYSTEM);
		}
	}
	
	if ($nodeParent) {
		
		// For form managers, add the privileged maintenance and enquiry options
		if ($manager) {
			$node = $nodeParent->add(get_string('settings_nav', 'local_obu_forms'), '/local/obu_forms/forms.php');
			$node = $nodeParent->add(get_string('template_nav', 'local_obu_forms'), '/local/obu_forms/template.php');
			$node = $nodeParent->add(get_string('auths_nav', 'local_obu_forms'), '/local/obu_forms/auths.php');
			$node = $nodeParent->add(get_string('sc_auths', 'local_obu_forms'), '/local/obu_forms/auths.php?authoriser=csa');
			$node = $nodeParent->add(get_string('list_users_forms', 'local_obu_forms'), '/local/obu_forms/list.php');
			$node = $nodeParent->add(get_string('formslist', 'local_obu_forms'), '/local/obu_forms/formslist.php');
		} else { // For other users, add the option(s) to list all the relevant forms
			if ($staff) {
				$node = $nodeParent->add(get_string('staff_forms', 'local_obu_forms'), '/local/obu_forms/formslist.php?type=staff');
			}
			$node = $nodeParent->add(get_string('student_forms', 'local_obu_forms'), '/local/obu_forms/formslist.php?type=student'); // Both staff and students can view student forms
			if ($staff) {
				$node = $nodeParent->add(get_string('list_users_forms', 'local_obu_forms'), '/local/obu_forms/list.php');
			}
		}
	}
}
