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
 * OBU Forms - Upload course custom fields (course)
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

class local_obu_forms_upload_course {

    /** Outcome of the process: updating the course */
    const DO_UPDATE = 2;

    /** @var array errors. */
    protected $errors = array();

    /** @var int the ID of the course being processed. */
    protected $id;

    /** @var int constant value of self::DO_*, what to do with that course */
    protected $do;

    /** @var bool set to true once we have prepared the course */
    protected $prepared = false;

    /** @var bool set to true once we have started the process of the course */
    protected $processstarted = false;

    /** @var array custom field import data. */
    protected $data;

    /** @var string course shortname. */
    protected $shortname;

    /** @var array statuses. */
    protected $statuses = array();

    /**
     * Constructor
     *
     * @param array $rawdata raw course data.
     */
    public function __construct($rawdata) {

        if (isset($rawdata['shortname'])) {
            $this->shortname = $rawdata['shortname'];
        }
        $this->data = $rawdata;
    }

    /**
     * Log an error
     *
     * @param string $code error code.
     * @param lang_string $message error message.
     * @return void
     */
    protected function error($code, lang_string $message) {
        if (array_key_exists($code, $this->errors)) {
            throw new coding_exception('Error code already defined');
        }
        $this->errors[$code] = $message;
    }

    /**
     * Return whether the course exists or not.
     *
     * @param string $shortname the shortname to use to check if the course exists. Falls back on $this->shortname if empty.
     * @return bool
     */
    protected function exists($shortname = null) {
        global $DB;
        if (is_null($shortname)) {
            $shortname = $this->shortname;
        }
        if (!empty($shortname) || is_numeric($shortname)) {
            return $DB->record_exists('course', array('shortname' => $shortname));
        }
        return false;
    }

    /**
     * Return the data that will be used upon saving.
     *
     * @return null|array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Return the ID of the processed course.
     *
     * @return int|null
     */
    public function get_id() {
        if (!$this->processstarted) {
            throw new coding_exception('The course has not been processed yet!');
        }
        return $this->id;
    }

    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_statuses() {
        return $this->statuses;
    }

    /**
     * Return whether there were errors with this course.
     *
     * @return boolean
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    /**
     * Validates and prepares the data.
     *
     * @return bool false if any error occurred.
     */
    public function prepare() {
        $this->prepared = true;

        // Validate the shortname.
        if (!empty($this->shortname) || is_numeric($this->shortname)) {
            if ($this->shortname !== clean_param($this->shortname, PARAM_TEXT)) {
                $this->error('invalidshortname', new lang_string('invalidshortname', 'local_obu_forms'));
                return false;
            }
        }

        $exists = $this->exists();
        if (!$exists) {
            $this->error('invalidshortname', new lang_string('invalidshortname', 'local_obu_forms'));
            return false;
        }

        return true;
    }

    /**
     * Proceed with the update of the course custom fields.
     *
     * @return void
     */
    public function proceed() {
        global $DB;

        if (!$this->prepared) {
            throw new coding_exception('The course has not been prepared.');
        } else if ($this->has_errors()) {
            throw new moodle_exception('Cannot proceed, errors were detected.');
        } else if ($this->processstarted) {
            throw new coding_exception('The process has already been started.');
        }
        $this->processstarted = true;
		
        $course = $DB->get_record('course', array('shortname' => $this->shortname), 'id', MUST_EXIST);
		$this->id = $course->id;

		$handler = core_course\customfield\course_handler::create();
		$fields = $handler->export_instance_data_object($this->id, true);
		
		// Do some mapping (!!!TODO: SHOULD REFER TO CUSTOM FIELD DEFINITIONS!!!)
		$mapped = [];
		foreach ($fields as $id => $value) {
			if ($value == 'Yes') {
				$mapped[$id] = '1';
			} else if ($value == 'No') {
				$mapped[$id] = '0';
			} else {
				$mapped[$id] = $value;
			}
		}

		$data = new stdClass();
		$fields_updated = false;
		foreach ($this->data as $id => $value) {
			if (array_key_exists($id, $mapped) && ($mapped[$id] != $value)) {
				$fieldname = 'customfield_' . $id;
				$data->$fieldname = $value;
				$fields_updated = true;
			}
		}
		if ($fields_updated) {
			$handler = core_course\customfield\course_handler::create();
			$data->id = $this->id;
			$handler->instance_form_save($data);
			$this->status('courseupdated', new lang_string('courseupdated', 'local_obu_forms'));
		}

		return;
    }

    /**
     * Log a status
     *
     * @param string $code status code.
     * @param lang_string $message status message.
     * @return void
     */
    protected function status($code, lang_string $message) {
        if (array_key_exists($code, $this->statuses)) {
            throw new coding_exception('Status code already defined');
        }
        $this->statuses[$code] = $message;
    }

}
