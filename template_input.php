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
 * OBU Forms - Template input form
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class template_input extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->formref = $this->_customdata['formref'];
		$data->formname = $this->_customdata['formname'];
		$data->version = $this->_customdata['version'];
		$data->versions = $this->_customdata['versions'];
		$data->record = $this->_customdata['record'];
		
		$already_published = 0;
		if ($data->record != null) {
			$template['text'] = $data->record->data;
			$already_published = $data->record->published;
			$fields = [
				'data' => $template,
				'published' => $already_published
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('amend_template', 'local_obu_forms') . '</h2>');

		if ($data->formref == '') {
			$mform->addElement('text', 'formref', get_string('form', 'local_obu_forms'), 'size="10" maxlength="10"');
			$mform->setType('formref', PARAM_RAW);
			$this->add_action_buttons(false, get_string('continue', 'local_obu_forms'));
			return;
		}
		$mform->addElement('hidden', 'formref', strtoupper($data->formref));
		$mform->setType('formref', PARAM_RAW);
		$mform->addElement('static', null, get_string('form', 'local_obu_forms'), strtoupper($data->formref));
		$mform->addElement('static', null, get_string('form_name', 'local_obu_forms'), $data->formname);
		
		if ($data->version == '') {
			if (!$data->versions) {
				$mform->addElement('text', 'version', get_string('version', 'local_obu_forms'), 'size="10" maxlength="10"');
				$mform->setType('version', PARAM_RAW);
			} else {
				$select = $mform->addElement('select', 'versions', get_string('version', 'local_obu_forms'), $data->versions, null);
				$select->setSelected(0);
			}
			$this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
			return;
		}
		$mform->addElement('hidden', 'version', strtoupper($data->version));
		$mform->setType('version', PARAM_RAW);
		$mform->addElement('static', null, get_string('version', 'local_obu_forms'), strtoupper($data->version));

		//  Allow a site admin to rename a version
		if (is_siteadmin()) {
			$mform->addElement('text', 'new_version', get_string('new_version', 'local_obu_forms'), 'size="10" maxlength="10"');
		} else {
			$mform->addElement('hidden', 'new_version', '');
		}
		$mform->setType('new_version', PARAM_RAW);

		$mform->addElement('editor', 'data', get_string('template', 'local_obu_forms'));
		$mform->setType('data', PARAM_RAW);
		$mform->disabledIf('data', 'published', 'checked');

		if ($already_published) {
			$mform->addElement('hidden', 'already_published', 1);
			$mform->setType('already_published', PARAM_RAW);
			$mform->addElement('hidden', 'published', 1);
			$mform->setType('published', PARAM_RAW);
			$mform->addElement('html', '<strong>' . get_string('published', 'local_obu_forms') . '</strong>' . get_string('publish_note', 'local_obu_forms'));
		} else {
			$mform->addElement('hidden', 'already_published', 0);
			$mform->setType('already_published', PARAM_RAW);
			$mform->addElement('advcheckbox', 'published', get_string('publish', 'local_obu_forms'), get_string('publish_note', 'local_obu_forms'), null, array(0, 1));
			$mform->disabledIf('published', 'published', 'checked');
		}

        $this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
    }
}