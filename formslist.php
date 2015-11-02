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
 * OBU Forms - List all available forms
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');

require_login();
$context = context_system::instance();

$type = optional_param('type', '', PARAM_TEXT);

$url = new moodle_url('/local/obu_forms/formslist.php?type=' . $type);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
if ($type == 'staff') {
	$heading = get_string('staff_forms', 'local_obu_forms');
} else if ($type == 'student') {
	$heading = get_string('student_forms', 'local_obu_forms');
} else {
	$heading = get_string('formslist', 'local_obu_forms');
}
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$manager = has_capability('local/obu_forms:manage', $context); // Can view all forms
$staff = ((substr($USER->username, 0, 1) == 'p') && is_numeric(substr($USER->username, 1))); // Can view staff forms
$student = $staff || !empty(get_current_courses('P', $USER->id)); // Can view student forms

if ($type == 'student') { // Exclude staff forms
	$staff = false;
}

if ($type == 'staff') { // Exclude student forms
	$student = false;
}

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forms = get_forms($manager, $staff, $student);

$url = new moodle_url('/local/obu_forms/form.php');
foreach ($forms as $form) {
	echo '<h3><a href="' . $url . '?ref=' . $form->formref . '">' . $form->formref . ': ' . $form->name . '</a></h3>';
	echo $form->description;
}

echo $OUTPUT->footer();


