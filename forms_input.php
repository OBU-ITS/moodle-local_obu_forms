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
 * OBU Forms - Settings input form
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class settings_input extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->formref = $this->_customdata['formref'];
		$data->record = $this->_customdata['record'];
		$data->form_indicator = $this->_customdata['form_indicator'];
		$data->student_indicator = $this->_customdata['student_indicator'];
		
		if ($data->record != null) {
			$description['text'] = $data->record->description;
			$fields = [
				'name' => $data->record->name,
				'description' => $description,
				'modular' => $data->record->modular,
				'student' => $data->record->student,
				'visible' => $data->record->visible,
				'auth_1_role' => $data->record->auth_1_role,
				'auth_1_notes' => $data->record->auth_1_notes,
				'auth_2_role' => $data->record->auth_2_role,
				'auth_2_notes' => $data->record->auth_2_notes,
				'auth_3_role' => $data->record->auth_3_role,
				'auth_3_notes' => $data->record->auth_3_notes,
				'auth_4_role' => $data->record->auth_4_role,
				'auth_4_notes' => $data->record->auth_4_notes,
				'auth_5_role' => $data->record->auth_5_role,
				'auth_5_notes' => $data->record->auth_5_notes,
				'auth_6_role' => $data->record->auth_6_role,
				'auth_6_notes' => $data->record->auth_6_notes
			];
			$this->set_data($fields);
		} else if (($data->form_indicator > 0) || ($data->student_indicator > 0)) { // We have presets for the new form
			$fields = array();
			if ($data->form_indicator > 0) {
				$fields['modular'] = ($data->form_indicator - 1);
			}
			if ($data->student_indicator > 0) {
				$fields['student'] = ($data->student_indicator - 1);
			}
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('amend_settings', 'local_obu_forms') . '</h2>');

		if ($data->formref == '') {
			$mform->addElement('text', 'formref', get_string('form', 'local_obu_forms'), 'size="10" maxlength="10"');
			$mform->setType('formref', PARAM_RAW);
			$this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
			return;
		}
		$mform->addElement('hidden', 'formref', strtoupper($data->formref));
		$mform->setType('formref', PARAM_RAW);
		$mform->addElement('hidden', 'form_indicator', $data->form_indicator);
		$mform->setType('form_indicator', PARAM_RAW);
		$mform->addElement('hidden', 'student_indicator', $data->student_indicator);
		$mform->setType('student_indicator', PARAM_RAW);
		$mform->addElement('static', null, get_string('form', 'local_obu_forms'), strtoupper($data->formref));
		
		$mform->addElement('text', 'name', get_string('form_name', 'local_obu_forms'), 'size="60" maxlength="60"');
		$mform->setType('name', PARAM_RAW);
		$mform->addElement('editor', 'description', get_string('description', 'local_obu_forms'));
		$mform->setType('description', PARAM_RAW);
		$mform->addElement('advcheckbox', 'modular', get_string('modular_form', 'local_obu_forms'), null, null, array(0, 1));
		$mform->disabledIf('modular', 'form_indicator', 'neq', '0');
		$mform->addElement('advcheckbox', 'student', get_string('student_form', 'local_obu_forms'), null, null, array(0, 1));
		$mform->disabledIf('student', 'student_indicator', 'neq', '0');
		$mform->addElement('advcheckbox', 'visible', get_string('form_visible', 'local_obu_forms'), null, null, array(0, 1));
		
		$authorisers = get_authorisers();
		
		// Authoriser 1
		$select = $mform->addElement('select', 'auth_1_role', get_string('auth_1_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_1_role);
		}
		$mform->addElement('text', 'auth_1_notes', get_string('auth_1_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_1_notes', PARAM_RAW);
		
		// Authoriser 2
		$select = $mform->addElement('select', 'auth_2_role', get_string('auth_2_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_2_role);
		}
		$mform->addElement('text', 'auth_2_notes', get_string('auth_2_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_2_notes', PARAM_RAW);

		// Authoriser 3
		$select = $mform->addElement('select', 'auth_3_role', get_string('auth_3_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_3_role);
		}
		$mform->addElement('text', 'auth_3_notes', get_string('auth_3_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_3_notes', PARAM_RAW);

		// Authoriser 4
		$select = $mform->addElement('select', 'auth_4_role', get_string('auth_4_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_4_role);
		}
		$mform->addElement('text', 'auth_4_notes', get_string('auth_4_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_4_notes', PARAM_RAW);

		// Authoriser 5
		$select = $mform->addElement('select', 'auth_5_role', get_string('auth_5_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_5_role);
		}
		$mform->addElement('text', 'auth_5_notes', get_string('auth_5_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_5_notes', PARAM_RAW);

		// Authoriser 6
		$select = $mform->addElement('select', 'auth_6_role', get_string('auth_6_role', 'local_obu_forms'), $authorisers, null);
		if ($data->record != null) {
			$select->setSelected($data->record->auth_6_role);
		}
		$mform->addElement('text', 'auth_6_notes', get_string('auth_6_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$mform->setType('auth_6_notes', PARAM_RAW);

        $this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
    }
}