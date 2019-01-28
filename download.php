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
 * OBU Forms - Data download
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./download_form.php');

require_login();
$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$url = $home . 'local/obu_forms/download.php';
$context = context_system::instance();

$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('data_download', 'local_obu_forms'));

$message = '';

$mform = new download_form(null, array());

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
		
	$formref = strtoupper($mform_data->formref);
	$forms_data = get_forms_data($formref, $mform_data->date_from, $mform_data->date_to); // Get all selected forms data
	if (empty($forms_data)) {
		$message = get_string('no_forms', 'local_obu_forms');
	} else {
		$settings = read_form_settings_by_ref($formref);

		$headings = array('Submitted', 'Status');
		if (!$settings->student) { // A staff form
			$headings[] = 'Author ID';
			$headings[] = 'Author Name';
		}

		// For this selection, determine which of the form's templates have been used and the maximum authorisation level reached
		$template_ids = array();
		$max_authorisation_level = 0;
		foreach ($forms_data as $form_data) {
			if (!in_array($form_data->template_id, $template_ids)) { // Not recorded yet
				$template_ids[] = $form_data->template_id;
			}
			if ($form_data->authorisation_level > $max_authorisation_level) {
				$max_authorisation_level = $form_data->authorisation_level;
			}
		}

		// Scan each template to extract field info and to get a list of all possible data field IDs
		$template_ids = array_reverse($template_ids); // Most recent (relevant) first, please
		$template_fields = array();
		$data_ids = array();
		foreach ($template_ids as $template_id) {
			$template = read_form_template_by_id($template_id);
			$template_fields[$template_id] = template_fields($template->data);
			foreach ($template_fields[$template_id] as $element) {
				if (!in_array($element['id'], $data_ids)) {
					$data_ids[] = $element['id'];
					$headings[] = $element['value'];
				}
			}
		}
		
		// Add the minimum authorisation headings
		if ($max_authorisation_level > 0) {
			$headings[] = 'Authoriser 1 ID';
			$headings[] = 'Authoriser 1 Name';
			$headings[] = 'Authorisation 1 Date';
			$headings[] = 'Authorisation 1 Notes';
			if ($max_authorisation_level > 1) {
				$headings[] = 'Authoriser 2 ID';
				$headings[] = 'Authoriser 2 Name';
				$headings[] = 'Authorisation 2 Date';
				$headings[] = 'Authorisation 2 Notes';
				if ($max_authorisation_level > 2) {
					$headings[] = 'Authoriser 3 ID';
					$headings[] = 'Authoriser 3 Name';
					$headings[] = 'Authorisation 3 Date';
					$headings[] = 'Authorisation 3 Notes';
					if ($max_authorisation_level > 3) {
						$headings[] = 'Authoriser 4 ID';
						$headings[] = 'Authoriser 4 Name';
						$headings[] = 'Authorisation 4 Date';
						$headings[] = 'Authorisation 4 Notes';
						if ($max_authorisation_level > 4) {
							$headings[] = 'Authoriser 5 ID';
							$headings[] = 'Authoriser 5 Name';
							$headings[] = 'Authorisation 5 Date';
							$headings[] = 'Authorisation 5 Notes';
							if ($max_authorisation_level > 5) {
								$headings[] = 'Authoriser 6 ID';
								$headings[] = 'Authoriser 6 Name';
								$headings[] = 'Authorisation 6 Date';
								$headings[] = 'Authorisation 6 Notes';
							}
						}
					}
				}
			}
		}

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=' . $formref . '_' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'w');
		fputcsv($fp, $headings);
		foreach ($forms_data as $form_data) {
			$fields = array();
			$fields['Submitted'] = date('d/m/Y', $form_data->date);
			if ($form_data->authorisation_state == 1) {
				$fields['Status'] = get_string('rejected', 'local_obu_forms');
			} else if ($form_data->authorisation_state == 2) {
				$fields['Status'] = get_string('processed', 'local_obu_forms');
			} else {
				$fields['Status'] = get_string('submitted', 'local_obu_forms');
			}
			if (!$settings->student) {
				$author = get_complete_user_data('id', $form_data->author);
				$fields['Author ID'] = $author->username;
				$fields['Author Name'] = $author->firstname . ' ' . $author->lastname;
			}

			// Add all the possible form data field IDs
			foreach ($data_ids as $data_id) {
				$fields[$data_id] = '';
			}
			
			// Copy the data fields that are in this form's template
			load_form_fields($form_data, $data_fields);
			foreach ($template_fields[$form_data->template_id] as $element) {
				if ($element['type'] == 'date') {
					$fields[$element['id']] = date('d/m/Y', $data_fields[$element['id']]);
				} else {
					$fields[$element['id']] = $data_fields[$element['id']];
				}
			}

			// Add all the possible authorisation field IDs
			if ($max_authorisation_level > 0) {
				$fields['Authoriser 1 ID'] = '';
				$fields['Authoriser 1 Name'] = '';
				$fields['Authorisation 1 Date'] = '';
				$fields['Authorisation 1 Notes'] = '';
				if ($max_authorisation_level > 1) {
					$fields['Authoriser 2 ID'] = '';
					$fields['Authoriser 2 Name'] = '';
					$fields['Authorisation 2 Date'] = '';
					$fields['Authorisation 2 Notes'] = '';
					if ($max_authorisation_level > 2) {
						$fields['Authoriser 3 ID'] = '';
						$fields['Authoriser 3 Name'] = '';
						$fields['Authorisation 3 Date'] = '';
						$fields['Authorisation 3 Notes'] = '';
						if ($max_authorisation_level > 3) {
							$fields['Authoriser 4 ID'] = '';
							$fields['Authoriser 4 Name'] = '';
							$fields['Authorisation 4 Date'] = '';
							$fields['Authorisation 4 Notes'] = '';
							if ($max_authorisation_level > 4) {
								$fields['Authoriser 5 ID'] = '';
								$fields['Authoriser 5 Name'] = '';
								$fields['Authorisation 5 Date'] = '';
								$fields['Authorisation 5 Notes'] = '';
								if ($max_authorisation_level > 5) {
									$fields['Authoriser 6 ID'] = '';
									$fields['Authoriser 6 Name'] = '';
									$fields['Authorisation 6 Date'] = '';
									$fields['Authorisation 6 Notes'] = '';
								}
							}
						}
					}
				}
			}

			// Copy the relevant authorisation fields (NB a level may have been skipped)
			if ($form_data->auth_1_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_1_id);
				$fields['Authoriser 1 ID'] = $authoriser->username;
				$fields['Authoriser 1 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_1_date > 0) {
					$fields['Authorisation 1 Date'] = date('d/m/Y', $form_data->auth_1_date);
					$fields['Authorisation 1 Notes'] = $form_data->auth_1_notes;
				}
			}
			if ($form_data->auth_2_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_2_id);
				$fields['Authoriser 2 ID'] = $authoriser->username;
				$fields['Authoriser 2 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_2_date > 0) {
					$fields['Authorisation 2 Date'] = date('d/m/Y', $form_data->auth_2_date);
					$fields['Authorisation 2 Notes'] = $form_data->auth_2_notes;
				}
			}
			if ($form_data->auth_3_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_3_id);
				$fields['Authoriser 3 ID'] = $authoriser->username;
				$fields['Authoriser 3 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_3_date > 0) {
					$fields['Authorisation 3 Date'] = date('d/m/Y', $form_data->auth_3_date);
					$fields['Authorisation 3 Notes'] = $form_data->auth_3_notes;
				}
			}
			if ($form_data->auth_4_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_4_id);
				$fields['Authoriser 4 ID'] = $authoriser->username;
				$fields['Authoriser 4 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_4_date > 0) {
					$fields['Authorisation 4 Date'] = date('d/m/Y', $form_data->auth_4_date);
					$fields['Authorisation 4 Notes'] = $form_data->auth_4_notes;
				}
			}
			if ($form_data->auth_5_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_5_id);
				$fields['Authoriser 5 ID'] = $authoriser->username;
				$fields['Authoriser 5 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_5_date > 0) {
					$fields['Authorisation 5 Date'] = date('d/m/Y', $form_data->auth_5_date);
					$fields['Authorisation 5 Notes'] = $form_data->auth_5_notes;
				}
			}
			if ($form_data->auth_6_id > 0) {
				$authoriser = get_complete_user_data('id', $form_data->auth_6_id);
				$fields['Authoriser 6 ID'] = $authoriser->username;
				$fields['Authoriser 6 Name'] = $authoriser->firstname . ' ' . $authoriser->lastname;
				if ($form_data->auth_6_date > 0) {
					$fields['Authorisation 6 Date'] = date('d/m/Y', $form_data->auth_6_date);
					$fields['Authorisation 6 Notes'] = $form_data->auth_6_notes;
				}
			}
			
			fputcsv($fp, $fields);
		}
		fclose($fp);
		
		exit();
	}
}	 

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();

