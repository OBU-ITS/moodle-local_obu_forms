<?php
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
 * OBU Forms - List all authorisations requested (excluding CSA) or just those for a given authoriser (including CSA)
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();
$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$authoriser_username = optional_param('authoriser', '', PARAM_TEXT);
if ($authoriser_username) {
	$authoriser = get_complete_user_data('username', $authoriser_username);
	$url = new moodle_url('/local/obu_forms/auths.php', array('authoriser' => $authoriser_username));
	if ($authoriser->username == 'csa') {
		$heading = get_string('sc_auths', 'local_obu_forms');
		$authoriser_id = $authoriser->id;
	} else if ($authoriser->username == 'tpt') {
		$heading = get_string('tpt_auths', 'local_obu_forms');
		$authoriser_id = 0;
	} else {
		$heading = get_string('auths', 'local_obu_forms') . ': ' . $authoriser->firstname . ' ' . $authoriser->lastname;
		$authoriser_id = $authoriser->id;
	}
} else {
	$authoriser = get_complete_user_data('username', 'csa'); // So that we can exclude them later
	$authoriser_id = 0;
	$url = $home . 'local/obu_forms/auths.php';
	$heading = get_string('auths_title', 'local_obu_forms');
}

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$process = $home . 'local/obu_forms/process.php';
$redirect = $home . 'local/obu_forms/redirect.php';
$auths = get_form_auths($authoriser_id); // Get outstanding authorisation requests

foreach ($auths as $auth) {
	if (($authoriser_id != 0) || ($auth->authoriser != $authoriser->id)) {
		read_form_data($auth->data_id, $data);
		$template = read_form_template_by_id($data->template_id);
		$form = read_form_settings($template->form_id);
		
		// Ceheck that the form type is correct for this report
		if (($form->formref == 'M3') || ($form->formref == 'M200') || ($form->formref == 'M201') || ($form->formref == 'M201L')) {
			$tpt_form = true; // The responsibility of the Taught Programmes Team
		} else {
			$tpt_form = false;
		}
		if ((($authoriser->username == 'tpt') && !$tpt_form) || (($authoriser->username != 'tpt') && $tpt_form)) {
			continue;
		}
		
		// Check that the user is a manager of this type of form and that it hasn't already been finally approved or rejected
		if (is_manager($form) && ($data->authorisation_state == 0)) {
			get_form_status($USER->id, $form, $data, $text, $button); // Get the authorisation trail and the next action (from the user's perspective)

			// If a staff form, extract any given student number
			$student_number = '';
			if (!$form->student) {
				load_form_fields($data, $fields);
				if (array_key_exists('student_number', $fields)) {
					$student_number = ' [' . $fields['student_number'] . ']';
				}
			} else if ($form->modular) {
				$author = get_complete_user_data('id', $data->author);
				$student_number = ' [' . $author->username . ']';
			}

			echo '<h4><a href="' . $process . '?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>';
			echo $text . '<' . $form->formref . '>';
			if ($authoriser_username != 'csa') { // They can't redirect away from themselves
				echo '<p><a href="' . $redirect . '?id=' . $data->id . '">' . get_string('redirect_form', 'local_obu_forms') . '</a></p>';
			}
		}
	}
}

echo $OUTPUT->footer();


