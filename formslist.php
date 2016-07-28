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
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');

require_login();

$type = optional_param('type', '', PARAM_TEXT);

$url = new moodle_url('/local/obu_forms/formslist.php?type=' . $type);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
if ($type == 'staff') {
	$heading = get_string('staff_forms', 'local_obu_forms');
} else if ($type == 'student') {
	$heading = get_string('student_forms', 'local_obu_forms');
} else {
	$heading = get_string('formslist', 'local_obu_forms');
}
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$staff_forms = ((substr($USER->username, 0, 1) == 'p') && is_numeric(substr($USER->username, 1))); // Can view staff forms
$pg_forms = $staff_forms || is_student($USER->id, 'PG'); // Can view PG student forms
$ump_forms = $staff_forms || is_student($USER->id, 'UMP'); // Can view UMP student forms

if ($type == 'student') { // Exclude staff forms
	$staff_forms = false;
}

if ($type == 'staff') { // Exclude student forms
	$pg_forms = false;
	$ump_forms = false;
}

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forms = get_forms(is_manager(), $staff_forms, $pg_forms, $ump_forms);

$url = new moodle_url('/local/obu_forms/form.php');
foreach ($forms as $form) {
	echo '<h3><a href="' . $url . '?ref=' . $form->formref . '">' . $form->formref . ': ' . $form->name . '</a></h3>';
	echo $form->description;
}

echo $OUTPUT->footer();


