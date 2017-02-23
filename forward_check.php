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
 * @copyright  2017, Oxford Brookes University
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

$url = new moodle_url('/local/obu_forms/forward_check.php');
$context = context_system::instance();

$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('forward_check', 'local_obu_forms'));

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('forward_check', 'local_obu_forms'));

$forwarders = get_form_forwarders();

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
	echo '<h4><a href="' . $url . '?authoriser=' . $from->id . '">' . $from->username . ' (' . $from->firstname . ' ' . $from->lastname . ') to ' . $to->username . ' (' . $to->firstname . ' ' . $to->lastname . ')</a> : ' . $start . ' to ' . $stop . '</h4>';
}

echo $OUTPUT->footer();


