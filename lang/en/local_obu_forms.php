<?php

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
 * OBU Forms - language strings
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$string['pluginname'] = 'OBU Forms';
$string['obu_forms:manage_pg'] = 'Manage, view and authorise PG forms in obu_forms';
$string['obu_forms:manage_ump_staff'] = 'Manage, view and authorise UMP staff forms in obu_forms';
$string['obu_forms:manage_ump_students'] = 'Manage, view and authorise UMP student forms in obu_forms';

$string['forms'] = 'Forms';
$string['sc_auths'] = 'CSA/SC authorisations';
$string['formslist'] = 'Forms list';
$string['staff_forms'] = 'Staff request forms';
$string['student_forms'] = 'Student request forms';
$string['list_users_forms'] = 'List a user\'s forms';
$string['list_staff_forms'] = 'List a student\'s staff forms';
$string['redirect_form'] = 'Redirect form';
$string['forward_forms'] = 'Forward forms';
$string['myforms'] = 'My forms';

$string['continue'] = 'Continue';
$string['save'] = 'Save';
$string['submit'] = 'Submit';
$string['authorise'] = 'Authorise';
$string['reject'] = 'Reject';
$string['redirect'] = 'Redirect';
$string['comment'] = 'Comment or Reason for Rejection';

$string['invalid_data'] = 'Invalid Data';

$string['author'] = 'Author';
$string['date'] = 'Date';

$string['settings_title'] = 'Add or Amend a Form\'s Settings';
$string['settings_nav'] = 'Form settings';
$string['amend_settings'] = 'Add or Amend Form Settings';
$string['form_name'] = 'Form Name';
$string['description'] = 'Description';
$string['modular_form'] = 'UMP Form';
$string['student_form'] = 'Student Form';
$string['form_visible'] = 'Form Visible';
$string['auth_1_role'] = 'Role for First Authorisation';
$string['auth_1_notes'] = 'Notes for First Authorisation';
$string['auth_2_role'] = 'Role for Second Authorisation';
$string['auth_2_notes'] = 'Notes for Second Authorisation';
$string['auth_3_role'] = 'Role for Third Authorisation';
$string['auth_3_notes'] = 'Notes for Third Authorisation';
$string['auth_4_role'] = 'Role for Fourth Authorisation';
$string['auth_4_notes'] = 'Notes for Fourth Authorisation';
$string['auth_5_role'] = 'Role for Fifth Authorisation';
$string['auth_5_notes'] = 'Notes for Fifth Authorisation';

$string['template_title'] = 'Add or Amend a Form Template';
$string['template_nav'] = 'Form templates';
$string['amend_template'] = 'Add or Amend a Form Template';
$string['form'] = 'Form';
$string['version'] = 'Version';
$string['new_version'] = 'New Version';
$string['template'] = 'Template';
$string['publish'] = 'Publish';
$string['publish_note'] = ' (Note - a published template version can no longer be amended)';
$string['draft'] = 'Draft';
$string['published'] = 'Published';

$string['auths_title'] = 'Form Authorisations';
$string['auths_nav'] = 'Form authorisations';
$string['auths'] = 'Authorisations';

$string['forward_from'] = 'Authoriser to forward from';
$string['forward_to'] = 'Authoriser to forward to';
$string['forward_start'] = 'Date to start forwarding';
$string['forward_stop'] = 'Date to stop forwarding';
$string['invalid_date'] = 'Invalid date.';
$string['forward_text'] = '<h4>Notes</h4><ul><li>You must enter the number of the staff member from whom authorisations are to be automatically forwarded.  Only one forwarding instruction is allowed per staff member and a new instruction will replace any existing one.</li><li>If you do not enter a number for the staff member to whom authorisations are to be automatically forwarded, this will be taken to be a deletion.  If there is an existing instruction for the given \'from\' member of staff then this will be removed.</li><li>Both dates are inclusive</li></ul>';
$string['forward_check'] = 'Check forwarding';

$string['form_title'] = 'Form';

$string['user_number'] = 'User Number';
$string['student'] = 'Student';
$string['surname'] = 'Surname';
$string['forename'] = 'Forename(s)';
$string['subjects'] = 'Subjects';
$string['select'] = 'Please select';
$string['no_one'] = 'I have not spoken to anyone';
$string['start_date'] = 'Start Date';
$string['module_no'] = 'Module No';
$string['module_name'] = 'Module Name';
$string['start'] = 'Start';
$string['run'] = 'Run';

$string['add_modules'] = 'Modules to be ADDED';
$string['delete_modules'] = 'Modules to be DELETED';

$string['request_authorisation'] = '<p>N.B. This is an automatic email generated by Moodle.</p><p>The author of this email has submitted a {$a->form} which requires your approval as {$a->role}. Please follow the link to see the details of the request and approve/reject it using the buttons at the bottom of the form.</p><p>If you have any queries about this email, or the forms themselves, then please don\'t hesitate to get in touch with {$a->name} on {$a->phone} or at {$a->email}.  Thank you.';
$string['submit_form'] = 'Please process the following {$a} form.<p />';

$string['status_not_submitted'] = 'Has not been submitted.';
$string['status_rejected'] = 'Cannot be processed.';
$string['status_processed'] = 'Has been processed.';

$string['actioned_by'] = '{$a->action} by {$a->by}.';
$string['submitted'] = 'Submitted';
$string['authorised'] = 'Authorised';
$string['rejected'] = 'Rejected';
$string['processed'] = 'Processed';

$string['awaiting_action'] = 'Awaiting {$a->action} by {$a->by}.';
$string['amendment'] = 'amendment/resubmission';
$string['submission'] = 'submission';
$string['authorisation'] = 'authorisation';

$string['form_unavailable'] = 'Sorry, this form is unavailable to you.';
$string['form_error'] = 'Your form can\'t be submitted (please see problems below).';
$string['value_required'] = 'You must supply a value here.';
$string['group_required'] = 'You must supply at least one value.';
$string['user_not_found'] = 'User not found.';
$string['user_invalid'] = 'User invalid.';
$string['course_not_found'] = 'Course not found.';
$string['module_not_found'] = 'Module not found.';
$string['invalid_module_code'] = 'Invalid module code.';
$string['invalid_date_format'] = 'Invalid date format (MMMYY).';
$string['invalid_start_date'] = 'Invalid start date.';
