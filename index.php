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
 * OBU Forms - List a user's forms
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

// Can only look at someone else's forms if we are a forms manager
if (is_manager()) {
	$user_id = optional_param('userid', 0, PARAM_INT);
} else {
	$user_id = 0;
}

$url = new moodle_url('/local/obu_forms/index.php', array('userid' => $user_id));
$redirect_form = new moodle_url('/local/obu_forms/redirect.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

if (($user_id == 0) || ($user_id == $USER->id)) {
    $user = $USER;
	$currentuser = true;
	$heading = get_string('myforms', 'local_obu_forms');
} else {
    $user = $DB->get_record('user', array('id' => $user_id));
    if (!$user) {
        print_error('invaliduserid');
    }
    $currentuser = false; // If we're looking at someone else's forms we may need to lock/remove some UI elements
    $PAGE->navigation->extend_for_user($user);
	$heading = get_string('forms', 'local_obu_forms') . ': ' . $user->firstname . ' ' . $user->lastname;
}

$PAGE->set_context(context_user::instance($user->id));
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forms_data = get_form_data(); // get all forms data [*** NEEDS ATTENTION IN FUTURE ***]

foreach ($forms_data as $data) {
	$template = read_form_template_by_id($data->template_id);
	$form = read_form_settings($template->form_id);
	
	// If a staff form, extract any given student number
	$student = '';
	$student_number = '';
	if (!$form->student) {
		load_form_fields($data, $fields);
		if (array_key_exists('student_number', $fields)) {
			$student = $fields['student_number'];
			$student_number = ' [' . $student . ']';
		}
	}
	
	if ((($data->author == $user->id) && ($currentuser || is_manager($form))) // We can normally only look at our own forms or forms that we manage...
		|| (($student == $user->username) && is_manager($form))) { // ...but managers can also look at staff forms that relate to the user (student)
		get_form_status($user->id, $form, $data, $text, $button); // Get the authorisation trail and the next action (from the user's perspective)
		
		if ($button != 'submit') {
			$url = new moodle_url('/local/obu_forms/process.php');
		} else if ($currentuser) {
			$url = new moodle_url('/local/obu_forms/form.php');
		} else {
			$url = '';
		}
	
		if ($url) {
			echo '<h4><a href="' . $url . '?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>';
		} else {
			echo '<h4>' . $form->formref . ': ' . $form->name . $student_number . '</h4>';
		}
		echo $text;
		
		// Allow form to be directed if possible
		if (is_manager($form) && ($data->authorisation_state == 0)) { // Not yet finally approved or rejected
			echo '<p><a href="' . $redirect_form . '?id=' . $data->id . '">' . get_string('redirect_form', 'local_obu_forms') . '</a></p>';
		}
	}
}

echo $OUTPUT->footer();


