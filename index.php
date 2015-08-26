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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();
$context = context_system::instance();
$manager = has_capability('local/obu_forms:manage', $context);

$user_id = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/local/obu_forms/index.php', array('userid' => $user_id));
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

$forms_data = get_form_data($user->id); // get all forms data

foreach ($forms_data as $data) {
	$template = read_form_template_by_id($data->template_id);
	$form = read_form_settings($template->form_id);
	get_form_status($USER->id, $data, $text, $button); // get the authorisation trail and the next action (from the user's perspective)
	if ($button != 'submit') {
		$url = new moodle_url('/local/obu_forms/process.php');
	} else if ($currentuser) {
		$url = new moodle_url('/local/obu_forms/form.php');
	} else {
		$url = '';
	}
	
	if ($url) {
		echo '<h4><a href="' . $url . '?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . '</a></h4>';
	} else {
		echo '<h4>' . $form->formref . ': ' . $form->name . '</h4>';
	}
	echo $text;
}

echo $OUTPUT->footer();


