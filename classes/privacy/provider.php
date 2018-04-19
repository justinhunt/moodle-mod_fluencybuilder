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
 * Privacy Subsystem implementation for assignsubmission_onlinepoodll.
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlinepoodll\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/onlinepoodll/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \mod_assign\privacy\submission_request_data;

/**
 * Privacy Subsystem for assignsubmission_onlinepoodll implementing null_provider.
 *
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, \mod_assign\privacy\assignsubmission_provider {
    use \core_privacy\local\legacy_polyfill;
    use \mod_assign\privacy\assignsubmission_provider\legacy_polyfill;


    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function _get_metadata(collection $collection) {
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');
        return $collection;
    }
    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function _get_context_for_userid_within_submission($userid, contextlist $contextlist) {
        // This is already fetched from mod_assign.
    }
    /**
     * This is also covered by the mod_assign provider and it's queries.
     *
     * @param  \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function _get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
        // No need.
    }
    /**
     * Export all user data for this plugin.
     *
     * @param  submission_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function _export_submission_user_data(submission_request_data $exportdata) {
        // We currently don't show submissions to teachers when exporting their data.
        $context = $exportdata->get_context();
        if ($exportdata->get_user() != null) {
            return null;
        }
        $user = new \stdClass();
        $plugin = $exportdata->get_subplugin();
        $files = $plugin->get_files($exportdata->get_submission(), $user);
        foreach ($files as $file) {
            $userid = $exportdata->get_submission()->userid;
            writer::with_context($exportdata->get_context())->export_file($exportdata->get_subcontext(), $file);
            // Plagiarism data.
            $coursecontext = $context->get_course_context();
            \core_plagiarism\privacy\provider::export_plagiarism_user_data($userid, $context, $exportdata->get_subcontext(), [
                'cmid' => $context->instanceid,
                'course' => $coursecontext->instanceid,
                'userid' => $userid,
                'file' => $file
            ]);
        }
    }
    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param  submission_request_data $requestdata Information useful for deleting user data.
     */
    public static function _delete_submission_for_context(submission_request_data $requestdata) {
        global $DB;
        \core_plagiarism\privacy\provider::delete_plagiarism_for_context($requestdata->get_context());
        $fs = get_file_storage();
        $fs->delete_area_files($requestdata->get_context()->id, ASSIGNSUBMISSION_ONLINEPOODLL_COMPONENT, ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA);
        // Delete records from assignsubmission_file table. -- Could use the assignment id.....
        $DB->delete_records(ASSIGNSUBMISSION_ONLINEPOODLL_TABLE, ['assignment' => $requestdata->get_assign()->get_instance()->id]);
    }
    /**
     * A call to this method should delete user data (where practicle) using the userid and submission.
     *
     * @param  submission_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function _delete_submission_for_userid(submission_request_data $exportdata) {
        global $DB;
        \core_plagiarism\privacy\provider::delete_plagiarism_for_user($exportdata->get_user()->id, $exportdata->get_context());
        $submissionid = $exportdata->get_submission()->id;
        $fs = get_file_storage();
        $fs->delete_area_files($exportdata->get_context()->id, ASSIGNSUBMISSION_ONLINEPOODLL_COMPONENT, ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA,
            $submissionid);
        $DB->delete_records(ASSIGNSUBMISSION_ONLINEPOODLL_TABLE, ['assignment' => $exportdata->get_assign()->get_instance()->id,
            'submission' => $submissionid]);
    }


}
