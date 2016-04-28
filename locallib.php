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
 * Internal library of functions for module selfgroup
 *
 * All the selfgroup specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/group/lib.php");

/*
 * Check if selfgroup activity is available for group creation by students
 *
 * @param object $selfgroup selfgroup record
 * @return bool True if the selfgroup activity is available right now, false otherwise
 */
function selfgroup_is_available($selfgroup) {
    $now = time();
    return ($selfgroup->timestartenrollment < $now);
}

/**
 * Get the number of members in all groups
 *
 * @param $cm Course module slot of the selfgroup instance
 * @return array of objects: [id] => object(->usercount ->id) where id is group id
 */
function selfgroup_group_member_counts($cm) {

    global $DB;

    // Get all groupmembers.
    $sql = "SELECT g.id, COUNT(gm.userid) AS usercount
                  FROM {groups_members} gm
                       JOIN {groups} g ON g.id = gm.groupid
                 WHERE g.courseid = :course
              GROUP BY g.id";
    $params = array('course' => $cm->course);

    return $DB->get_records_sql($sql, $params);
}

/**
 *
 * Check if the activity is completed, not completed or if no conditions are set
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function selfgroup_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get selfgroup details.
    if (!($selfgroup = $DB->get_record('selfgroup', array('id' => $cm->instance)))) {
        throw new Exception("Can't find selfgroup activity {$cm->instance}");
    }

    // If completion option is enabled, evaluate it and return true/false.
    if ($selfgroup->completionenabled) {

        // Completed when User is member of a group.
        $usergroups = groups_get_all_groups($course->id, $userid, null, 'g.*');
        if (!empty($usergroups)) {
            return true;
        } else {
            return false;
        }
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}