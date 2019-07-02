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
 * OBU Forms - Provide left hand navigation link
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/obu_forms/db_update.php');

function local_obu_forms_extend_navigation($navigation) {
    global $USER;
	
	if (!isloggedin() || isguestuser()) {
		return;
	}
	
	if (($USER->username != 'accommodation') && !is_staff($USER->username) && !is_student($USER->id) && empty(get_form_data($USER->id))) {
		return;
	}
	 
	$nodeHome = $navigation->children->get('1')->parent;
	$node = $nodeHome->add(get_string('forms', 'local_obu_forms'), '/local/obu_forms/menu.php', navigation_node::TYPE_SYSTEM);
	$node->showinflatnavigation = true;
}
