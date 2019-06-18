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
 * OBU Forms - Privacy Subsystem implementation
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_obu_forms\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {

	public static function get_metadata(collection $collection) : collection {
	 
		$collection->add_database_table(
			'local_obu_forms_data',
			[
				'id' => 'privacy:metadata:local_obu_forms:id',
				'author' => 'privacy:metadata:local_obu_forms:author',
				'date' => 'privacy:metadata:local_obu_forms:date',
				'authorisation_state' => 'privacy:metadata:local_obu_forms:authorisation_state',
				'auth_1_id' => 'privacy:metadata:local_obu_forms:auth_1_id',
				'auth_1_notes' => 'privacy:metadata:local_obu_forms:auth_1_notes',
				'auth_1_date' => 'privacy:metadata:local_obu_forms:auth_1_date',
				'auth_2_id' => 'privacy:metadata:local_obu_forms:auth_2_id',
				'auth_2_notes' => 'privacy:metadata:local_obu_forms:auth_2_notes',
				'auth_2_date' => 'privacy:metadata:local_obu_forms:auth_2_date',
				'auth_3_id' => 'privacy:metadata:local_obu_forms:auth_3_id',
				'auth_3_notes' => 'privacy:metadata:local_obu_forms:auth_3_notes',
				'auth_3_date' => 'privacy:metadata:local_obu_forms:auth_3_date',
				'auth_4_id' => 'privacy:metadata:local_obu_forms:auth_4_id',
				'auth_4_notes' => 'privacy:metadata:local_obu_forms:auth_4_notes',
				'auth_4_date' => 'privacy:metadata:local_obu_forms:auth_4_date',
				'auth_5_id' => 'privacy:metadata:local_obu_forms:auth_5_id',
				'auth_5_notes' => 'privacy:metadata:local_obu_forms:auth_5_notes',
				'auth_5_date' => 'privacy:metadata:local_obu_forms:auth_5_date',
				'auth_6_id' => 'privacy:metadata:local_obu_forms:auth_6_id',
				'auth_6_notes' => 'privacy:metadata:local_obu_forms:auth_6_notes',
				'auth_6_date' => 'privacy:metadata:local_obu_forms:auth_6_date',
				'data' => 'privacy:metadata:local_obu_forms:data'
			],
			'privacy:metadata:local_obu_forms'
		);
	 
		return $collection;
	}

	public static function get_contexts_for_userid(int $userid) : contextlist {

		$sql = "SELECT DISTINCT c.id FROM {context} c
			JOIN {local_obu_forms_data} fd ON fd.author = c.instanceid
			WHERE (c.contextlevel = :contextlevel) AND (c.instanceid = :userid)";

		$params = [
			'contextlevel' => CONTEXT_USER,
			'userid' => $userid
		];

		$contextlist = new \core_privacy\local\request\contextlist();
		$contextlist->add_from_sql($sql, $params);

		return $contextlist;
	} 

	public static function export_user_data(approved_contextlist $contextlist) {
		global $DB;

		if (empty($contextlist->count())) {
			return;
		}

		$user = $contextlist->get_user();

		foreach ($contextlist->get_contexts() as $context) {

			if ($context->contextlevel != CONTEXT_USER) {
				continue;
			}

			$recs = $DB->get_records('local_obu_forms_data', ['author' => $user->id]);
			foreach ($recs as $rec) {
				$template = $DB->get_record('local_obu_forms_templates', ['id' => $rec->template_id]);
				$form = $DB->get_record('local_obu_forms', ['id' => $template->form_id]);
				$data = new \stdClass;
				$data->formref = $form->formref;
				$data->name = $form->name;
				if ($rec->date == 0) {
					$data->date = '';
				} else {
					$data->date = transform::datetime($rec->date);
				}
				if ($rec->authorisation_state == 1) {
					$data->authorisation_state = get_string('rejected', 'local_obu_forms');
				} else if ($rec->authorisation_state == 2) {
					$data->authorisation_state = get_string('authorised', 'local_obu_forms');
				} else {
					$data->authorisation_state = get_string('submitted', 'local_obu_forms');
				}
				if ($rec->auth_1_id == 0) {
					$data->auth_1_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_1_id]);
					$data->auth_1_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_1_notes = $rec->auth_1_notes;
				if ($rec->auth_1_date == 0) {
					$data->auth_1_date = '';
				} else {
					$data->auth_1_date = transform::datetime($rec->auth_1_date);
				}
				if ($rec->auth_2_id == 0) {
					$data->auth_2_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_2_id]);
					$data->auth_2_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_2_notes = $rec->auth_2_notes;
				if ($rec->auth_2_date == 0) {
					$data->auth_2_date = '';
				} else {
					$data->auth_2_date = transform::datetime($rec->auth_2_date);
				}
				if ($rec->auth_3_id == 0) {
					$data->auth_3_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_3_id]);
					$data->auth_3_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_3_notes = $rec->auth_3_notes;
				if ($rec->auth_3_date == 0) {
					$data->auth_3_date = '';
				} else {
					$data->auth_3_date = transform::datetime($rec->auth_3_date);
				}
				if ($rec->auth_4_id == 0) {
					$data->auth_4_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_4_id]);
					$data->auth_4_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_4_notes = $rec->auth_4_notes;
				if ($rec->auth_4_date == 0) {
					$data->auth_4_date = '';
				} else {
					$data->auth_4_date = transform::datetime($rec->auth_4_date);
				}
				if ($rec->auth_5_id == 0) {
					$data->auth_5_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_5_id]);
					$data->auth_5_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_5_notes = $rec->auth_5_notes;
				if ($rec->auth_5_date == 0) {
					$data->auth_5_date = '';
				} else {
					$data->auth_5_date = transform::datetime($rec->auth_5_date);
				}
				if ($rec->auth_6_id == 0) {
					$data->auth_6_name = '';
				} else {
					$auth = $DB->get_record('user', ['id' => $rec->auth_6_id]);
					$data->auth_6_name = $auth->firstname . ' ' . $auth->lastname;
				}
				$data->auth_6_notes = $rec->auth_6_notes;
				if ($rec->auth_6_date == 0) {
					$data->auth_6_date = '';
				} else {
					$data->auth_6_date = transform::datetime($rec->auth_6_date);
				}
				$xml = new \SimpleXMLElement($rec->data);
				$fields = array();
				foreach ($xml as $key => $value) {
					if (($key != 'source') && ($key != 'template') && ($key != 'auth_state') && ($key != 'auth_level')) {
						$fields[$key] = (string)$value;
					}
				}
				$data->data = $fields;

				writer::with_context($context)->export_data([get_string('privacy:obu_forms', 'local_obu_forms'), get_string('privacy:obu_form', 'local_obu_forms', $rec->id)], $data);
			}
		}

		return;
	}

	public static function delete_data_for_all_users_in_context(\context $context) {

		if ($context->contextlevel == CONTEXT_USER) {
			self::delete_data($context->instanceid);
		}
		
		return;
	}

	public static function delete_data_for_user(approved_contextlist $contextlist) {

		if (empty($contextlist->count())) {
			return;
		}

		$userid = $contextlist->get_user()->id;
		foreach ($contextlist->get_contexts() as $context) {
			if ($context->contextlevel == CONTEXT_USER) {
				self::delete_data($userid);
			}
		}
		
		return;
	}

	public static function get_users_in_context(userlist $userlist) {

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			$userlist->add_user($context->instanceid);
		}

		return;
	}

	public static function delete_data_for_users(approved_userlist $userlist) {

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			self::delete_data($context->instanceid);
		}

		return;
	}
	
	static function delete_data($userid) {
		global $DB;

		// Firstly, delete any outstanding authorisations
		$recs = $DB->get_records('local_obu_forms_data', ['author' => $userid]);
		foreach ($recs as $rec) {
			$DB->delete_records('local_obu_forms_auths', ['data_id' => $rec->id]);
		}

		// Now, the main event
		$DB->delete_records('local_obu_forms_data', ['author' => $userid]);

		return;
	}
}
