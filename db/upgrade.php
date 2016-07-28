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
 * OBU Forms - database upgrade
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function xmldb_local_obu_forms_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2015072000) {

		// Define table local_obu_forms
		$table = new xmldb_table('local_obu_forms');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('formref', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('author', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('name', XMLDB_TYPE_CHAR, '60', null, null, null, null);
		$table->add_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('student', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_1_role', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_1_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('auth_2_role', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_2_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('auth_3_role', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_3_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('formref', XMLDB_INDEX_UNIQUE, array('formref'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_forms_templates
		$table = new xmldb_table('local_obu_forms_templates');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('form_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('version', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('author', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('published', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('data', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('template', XMLDB_INDEX_UNIQUE, array('form_id', 'version'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
		
		// Define table local_obu_forms_data
		$table = new xmldb_table('local_obu_forms_data');

		// Add field
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('author', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('template_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('authorisation_state', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('authorisation_level', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_1_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_1_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('auth_1_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_2_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_2_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('auth_2_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_3_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('auth_3_notes', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('auth_3_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('data', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add indexes
        $table->add_index('author', XMLDB_INDEX_NOTUNIQUE, array('author'));

        // Conditionally create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

		// Define table local_obu_forms_auths
		$table = new xmldb_table('local_obu_forms_auths');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('data_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('authoriser', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('request_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('data_id', XMLDB_INDEX_NOTUNIQUE, array('data_id'));
		$table->add_index('authoriser', XMLDB_INDEX_NOTUNIQUE, array('authoriser'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

        // obu_forms savepoint reached
        upgrade_plugin_savepoint(true, 2015072000, 'local', 'obu_forms');
    }

	if ($oldversion < 2016071100) {

		// Define field modular to be added to local_obu_forms
		$table = new xmldb_table('local_obu_forms');
		$field = new xmldb_field('modular', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'description');

		// Conditionally launch add field modular
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_forms savepoint reached
		upgrade_plugin_savepoint(true, 2016071100, 'local', 'obu_forms');
    }
    
    return $result;
}




