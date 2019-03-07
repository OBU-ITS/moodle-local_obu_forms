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
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();

// Determine if the user can list the requested forms
$manager = is_manager();
if (!$manager && !is_staff($USER->username)) { // Students can only see their own forms
    $user = $USER;
} else {
	$user_id = optional_param('userid', 0, PARAM_INT);
	if (($user_id == 0) || ($user_id == $USER->id)) {
		$user = $USER;
	} else {
		$user = $DB->get_record('user', array('id' => $user_id));
		if (!$user) {
			print_error('invaliduserid');
		}
		if (is_staff($user->username) && !$manager) { // Only managers can view forms for other staff members
			$user = $USER;
		}
	}
}

$home = new moodle_url('/');
$dir = $home . 'local/obu_forms/';
$url = $dir . 'index.php?userid=' . $user_id;
$redirect_form = $dir . 'redirect.php';

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

if ($user->id == $USER->id) { // User
	$PAGE->set_context(context_user::instance($user->id));
	$currentuser = true;
	$title = get_string('myforms', 'local_obu_forms');
	$heading = get_string('myforms', 'local_obu_forms');
} else { // Forms management
	if ($manager) {
		require_login(get_forms_course());
	}
    $currentuser = false;
	$title = get_string('forms_management', 'local_obu_forms');
	$heading = get_string('forms', 'local_obu_forms') . ': ' . $user->firstname . ' ' . $user->lastname;
	$PAGE->navbar->add($heading);
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forms_data = get_form_data(); // get all forms data [*** NEEDS ATTENTION IN FUTURE ***]

foreach ($forms_data as $data) {
	$template = read_form_template_by_id($data->template_id);
	$form = read_form_settings($template->form_id);
	
	// Extract any module codes from a student form or (if a forms manager) any student number from a staff one
	$student = '';
	$subject = '';
	load_form_fields($data, $fields);
	if (is_manager($form) && array_key_exists('student_number', $fields)) {
		$student = $fields['student_number'];
		$subject .= ' [' . $student . ']';
	}
	if (is_manager($form) || $form->student) {
		$modules = '';
		foreach ($fields as $key => $value) {
			if ((strpos($key, 'module') !== false) && ($value != '')) {
				if ($modules != '') {
					$modules .= ', ';
				}
				$modules .= strtoupper($value);
			}
		}
		if ($modules != '') {
			$subject .= ' [' . $modules . ']';
		}
	}
	
	if (($data->author == $user->id) || ($student == $user->username)) {

		get_form_status($USER->id, $form, $data, $text, $button); // Get the authorisation trail and the next action (from the user's perspective)
		
		$url = '';
		if ($button == 'submit') {
			if ($currentuser) {
				echo '<h4><a href="' . $dir . 'form.php?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . $subject . '</a></h4>';
			}
		} else if ($currentuser || is_manager($form) || $button == 'authorise') { // Owner, manager or next authoriser
			echo '<h4><a href="' . $dir . 'process.php?source=' . urlencode('index.php?userid=' . $user_id) . '&id=' . $data->id . '">' . $form->formref . ': ' . $form->name . $subject . '</a></h4>';
		} else {
			echo '<h4>' . $form->formref . ': ' . $form->name . $subject . '</h4>';
		}
		echo $text;
		
		// Allow form to be redirected if possible
		if (is_manager($form) && ($data->authorisation_state == 0) && has_capability('local/obu_forms:update', context_system::instance())) { // Not yet finally approved or rejected
			echo '<p><a href="' . $redirect_form . '?id=' . $data->id . '">' . get_string('redirect_form', 'local_obu_forms') . '</a></p>';
		}
	}
}

echo $OUTPUT->footer();
