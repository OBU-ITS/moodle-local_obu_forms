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
 * OBU Forms - User Menu
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
$options = local_obu_forms_get_menu_options();
if (count($options) === 0) {
	redirect($home);
}

$dir = $home . 'local/obu_forms/';
$url = $dir . 'menu.php';

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_user::instance($USER->id));
$title = get_string('forms', 'local_obu_forms');
$heading = get_string('forms', 'local_obu_forms');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

foreach ($options as $option => $link) {
	echo '<h4><a href="' . $link . '">' . $option . '</a></h4>';
}

echo $OUTPUT->footer();
