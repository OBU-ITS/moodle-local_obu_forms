<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
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
 * OBU Forms - Bulk custom field upload from a comma separated file
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

admin_externalpage_setup('uploadcustomfields');

$importid = optional_param('importid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

$returnurl = new moodle_url('/local/obu_forms/uploadcustomfields.php');

if (empty($importid)) {
    $mform1 = new local_obu_forms_upload_form_1();
    if ($form1data = $mform1->get_data()) {
        $importid = csv_import_reader::get_new_iid('uploadcustomfields');
        $cir = new csv_import_reader($importid, 'uploadcustomfields');
        $content = $mform1->get_file_content('customfieldfile');
        $readcount = $cir->load_csv_content($content, $form1data->encoding, $form1data->delimiter_name);
        unset($content);
        if ($readcount === false) {
            print_error('csvfileerror', 'local_obu_forms', $returnurl, $cir->get_error());
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl, $cir->get_error());
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('uploadcustomfields', 'local_obu_forms'), 'uploadcustomfields', 'local_obu_forms');
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} else {
    $cir = new csv_import_reader($importid, 'uploadcustomfields');
}

// Data to set in the form.
$data = array('importid' => $importid, 'previewrows' => $previewrows);
$context = context_system::instance();
$mform2 = new local_obu_forms_upload_form_2(null, array('contextid' => $context->id, 'columns' => $cir->get_columns(), 'data' => $data));

// If a file has been uploaded, then process it.
if ($form2data = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
} else if ($form2data = $mform2->get_data()) {

    $processor = new local_obu_forms_upload_processor($cir);

    echo $OUTPUT->header();
    if (isset($form2data->showpreview)) {
        echo $OUTPUT->heading(get_string('uploadcustomfieldspreview', 'local_obu_forms'));
        $processor->preview($previewrows, new local_obu_forms_upload_tracker(local_obu_forms_upload_tracker::OUTPUT_HTML));
        $mform2->display();
    } else {
        echo $OUTPUT->heading(get_string('uploadcustomfieldsresult', 'local_obu_forms'));
        $processor->execute(new local_obu_forms_upload_tracker(local_obu_forms_upload_tracker::OUTPUT_HTML));
        echo $OUTPUT->continue_button($returnurl);
    }

    // Deleting the file after processing or preview.
    if (!empty($options['restorefile'])) {
        @unlink($options['restorefile']);
    }

} else {
    $processor = new local_obu_forms_upload_processor($cir);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadcustomfieldspreview', 'local_obu_forms'));
    $processor->preview($previewrows, new local_obu_forms_upload_tracker(local_obu_forms_upload_tracker::OUTPUT_HTML));
    $mform2->display();
}

echo $OUTPUT->footer();
