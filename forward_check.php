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
 * OBU Forms - Check forwarding
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

$home = new moodle_url('/');
if (!local_obu_forms_is_manager()) {
	redirect($home);
}

$forms_course = local_obu_forms_get_forms_course();
require_login($forms_course);
$back = $home . 'course/view.php?id=' . $forms_course;

$dir = $home . 'local/obu_forms/';
$url = $dir . 'forward_check.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('forward_check', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$forwarders = local_obu_forms_get_form_forwarders();

$url = new moodle_url('/local/obu_forms/forward.php');
$date = date_create();
$date_format = 'd-m-y';
foreach ($forwarders as $forwarder) {
	$from = get_complete_user_data('id', $forwarder->from_id);
	$to = get_complete_user_data('id', $forwarder->to_id);
	date_timestamp_set($date, $forwarder->start_date);
	$start = date_format($date, $date_format);
	date_timestamp_set($date, $forwarder->stop_date);
	$stop = date_format($date, $date_format);
	echo '<h4>';
	echo '<a href="' . $url . '?authoriser=' . $from->id . '">' . $from->username . ' (' . $from->firstname . ' ' . $from->lastname . ') to ' . $to->username . ' (' . $to->firstname . ' ' . $to->lastname . ')</a>';
	echo ' : ' . $start . ' to ' . $stop;
	if ($forwarder->updater_id > 0) {
		$updater = get_complete_user_data('id', $forwarder->updater_id);
		date_timestamp_set($date, $forwarder->update_date);
		$update_date = date_format($date, $date_format);
		echo ' [' . $updater->firstname . ' ' . $updater->lastname . ' ' . $update_date . ']';
	}
	echo '</h4>';
}

echo $OUTPUT->footer();
