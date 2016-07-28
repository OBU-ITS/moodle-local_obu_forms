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
 * OBU Forms - Purge erroneous authorisations
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

$url = $home . 'local/obu_forms/purge_auths.php';
$heading = get_string('auths_title', 'local_obu_forms');

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$auths = get_form_auths($authoriser_id); // Get outstanding authorisation requests

foreach ($auths as $auth) {
	read_form_data($auth->data_id, $data);
	$template = read_form_template_by_id($data->template_id);
	$form = read_form_settings($template->form_id);
		
	// Check first that the user is a manager of this type of form
	if (is_manager($form)) {
		get_form_status($USER->id, $data, $text, $button); // Get the authorisation trail and the next action (from the user's perspective)
		echo '<h4><a href="' . $process . '?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . '</a></h4>';
		echo $text . '<' . $form->formref . '>';
		if ($data->authorisation_state == 0) { // Not yet finally approved or rejected
			echo '<p>VALID</p>';
		} else {
			delete_form_auths($auth);
			echo '<p>*** PURGED ***</p>';
		}
	}
}

echo $OUTPUT->footer();


