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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();
$context = context_system::instance();
require_capability('local/obu_forms:manage', $context);

$authoriser_username = optional_param('authoriser', '', PARAM_TEXT);
if ($authoriser_username) {
	$authoriser = get_complete_user_data('username', $authoriser_username);
	$authoriser_id = $authoriser->id;
	$url = new moodle_url('/local/obu_forms/auths.php', array('authoriser' => $authoriser_username));
	$heading = get_string('auths', 'local_obu_forms') . ': ' . $authoriser->firstname . ' ' . $authoriser->lastname;
} else {
	$authoriser = get_complete_user_data('username', 'csa'); // So that we can exclude them later
	$authoriser_id = 0;
	$url = new moodle_url('/local/obu_forms/auths.php');
	$heading = get_string('auths_title', 'local_obu_forms');
}

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$process = new moodle_url('/local/obu_forms/process.php');
$redirect = new moodle_url('/local/obu_forms/redirect.php');
$auths = get_form_auths($authoriser_id); // get outstanding authorisation requests

foreach ($auths as $auth) {
	if (($authoriser_id != 0) || ($auth->authoriser != $authoriser->id)) {
		read_form_data($auth->data_id, $data);
		$template = read_form_template_by_id($data->template_id);
		$form = read_form_settings($template->form_id);
		get_form_status($USER->id, $data, $text, $button); // get the authorisation trail and the next action (from the user's perspective)
		echo '<h4><a href="' . $process . '?id=' . $data->id . '">' . $form->formref . ': ' . $form->name . '</a></h4>';
		echo $text;
		echo '<p><a href="' . $redirect . '?id=' . $data->id . '">' . get_string('redirect_form', 'local_obu_forms') . '</a></p>';
	}
}

echo $OUTPUT->footer();


