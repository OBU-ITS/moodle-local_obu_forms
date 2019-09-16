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
 * OBU Forms - Course Test input form
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class course_test_input extends moodleform {

    function definition() {
        $mform =& $this->_form;

		$mform->addElement('text', 'modular', get_string('modular', 'local_obu_forms'), 'size="4" maxlength="4"');
		$mform->addElement('text', 'user_id', get_string('user_id', 'local_obu_forms'), 'size="4" maxlength="4"');
		$mform->addElement('text', 'names', get_string('names', 'local_obu_forms'), 'size="4" maxlength="4"');
		$mform->addElement('text', 'joint', get_string('joint', 'local_obu_forms'), 'size="4" maxlength="4"');

		$this->add_action_buttons(true, get_string('submit', 'local_obu_forms'));
    }
}