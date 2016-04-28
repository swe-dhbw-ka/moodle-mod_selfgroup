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
 * Prints a particular instance of selfgroup
 *
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/creategroup_form.php');
require_once(dirname(__FILE__) . '/enrollgroup_form.php');
require_once(dirname(__FILE__) . '/editgroup_form.php');

// Check whether the groupoverview plugin is installed.
$groupoverviewinstalled = false;
$plugins = core_plugin_manager::instance()->get_plugins_of_type('mod');
if (isset($plugins['groupoverview'])) {
    $groupoverviewinstalled = true;
    require_once("$CFG->dirroot/mod/groupoverview/lib.php");
}

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or.
$s = optional_param('n', 0, PARAM_INT);  // ... selfgroup instance ID.
$create = optional_param('create', 0, PARAM_BOOL);
$enroll = optional_param('enroll', 0, PARAM_INT);
$unenroll = optional_param('unenroll', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('selfgroup', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $selfgroup = $DB->get_record('selfgroup', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($s) {
    $selfgroup = $DB->get_record('selfgroup', array('id' => $s), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $selfgroup->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('selfgroup', $selfgroup->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/selfgroup/view.php', array(
    'id' => $cm->id
));

$event = \mod_selfgroup\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $selfgroup);
$event->trigger();

// Add Completion on View Implementation.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print the page header.
$PAGE->set_url('/mod/selfgroup/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($selfgroup->name));
$PAGE->set_heading(format_string($course->fullname));

// Get data for further functionality of the plugin.
$isavailable = selfgroup_is_available($selfgroup);
$membercounts = selfgroup_group_member_counts($cm);

$usergroups = groups_get_all_groups($course->id, $USER->id, null, 'g.*');
$allgroups = groups_get_all_groups($course->id);
$allgroupings = array_values(groups_get_all_groupings($course->id));

// Add grouping names to array to display in select element.
$optiongroupings = array();

if (count($allgroupings) > 0) {
    foreach ($allgroupings as $grouping) {
        array_push($optiongroupings, $grouping->name);
    }
}

// Get information about groupoverviews if the plugin is installed.
$allgroupoverviewinstances = array();
$optiongroupoverviews = array();
$allgroupoverviews = array();
$allcategories = array();

if ($groupoverviewinstalled) {
    $allgroupoverviewinstances = $DB->get_records('groupoverview', array('course' => $course->id));

    if (count($allgroupoverviewinstances) > 0) {
        foreach ($allgroupoverviewinstances as $groupoverview) {

            $groupoverviewid = $groupoverview->id;
            $categories = groupoverview_get_categories($groupoverviewid);
            $categorynames = array();
            $categoryids = array();

            foreach ($categories as $category) {
                array_push($categorynames, $category->name);
                array_push($categoryids, $category->id);
                array_push($allcategories, $category);
            }

            $newentryname = array($groupoverviewid => $categorynames);
            $optiongroupoverviews = array_merge($optiongroupoverviews, $newentryname);

            $newentryid = array($groupoverviewid => $categoryids);
            $allgroupoverviews = array_merge($allgroupoverviews, $newentryid);
        }
    }
}

// Check Permissions.
$accessall = has_capability('moodle/site:accessallgroups', $context);
$viewfullnames = has_capability('moodle/site:viewfullnames', $context);
$canenroll = (has_capability('mod/selfgroup:enroll', $context) and is_enrolled($context) and empty ($usergroups));
$canunenroll = (has_capability('mod/selfgroup:unenroll', $context) and is_enrolled($context) and !empty ($usergroups));
$cancreate = (has_capability('mod/selfgroup:create', $context) and is_enrolled($context) and empty ($usergroups));
$canedit = ($isavailable);

// Check the role of the user if he is teacher or admin.
$isteacheroradmin = false;
if ($roles = get_user_roles($context, $USER->id)) {
    foreach ($roles as $role) {
        if ($role->roleid == 3) {
            $isteacheroradmin = true;
            break;
        }
    }
}
$admins = get_admins();
foreach ($admins as $admin) {
    if ($USER->id == $admin->id) {
        $isteacheroradmin = true;
        break;
    }
}

// Problem notification.
$problems = array();

// Only enrolled students of the course can create groups or enrol for a group.
if (!is_enrolled($context)) {
    $problems [] = get_string('notenrolled', 'selfgroup');
} else {
    if (!has_capability('mod/selfgroup:enroll', $context)) {
        $problems [] = get_string('cannotenrolnocap', 'selfgroup');
    }
}

// Add form to create new group.
if ($cancreate and $isavailable and $create) {

    $data = array('id' => $cm->id, 'groupings' => $optiongroupings, 'groupoverviews' => $optiongroupoverviews);

    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $course->maxbytes,
        'trust' => false, 'context' => $context, 'noclean' => true);

    $data['create'] = true;
    $createform = new creategroup_form(null, array($data, $selfgroup,
        $create));

    if ($createform->is_cancelled()) {

        redirect($PAGE->url);

    } else if ($data = $createform->get_data()) {

        $data->id = $cm->id;
        $data->courseid = $course->id;

        $link = $data->link;
        $groupdescription = $data->description_editor['text'] .
            '<p>Link zu Repository: <a href="http://' . $link . '">' . $link . '</a></p>';
        $data->description_editor['text'] = $groupdescription;

        // Create a new group.
        $groupid = groups_create_group($data, $createform, $editoroptions);

        // Assign created group to chosen grouping.
        $gid = $data->grouping;
        groups_assign_grouping($allgroupings[$gid]->id, $groupid, null, true);

        // Add creator of the group to the created group.
        groups_add_member($groupid, $USER->id);

        // Update completion state to complete.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $selfgroup->completionenabled) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }

        // Add mapping for created group to category if set.
        for ($i = 0; $i < count($allgroupoverviews); $i++) {
            $a = 'overview' . $i;

            if (isset($data->$a)) {
                $cid = $data->$a;
                $categoryid = $allgroupoverviews[$i][$cid];
                groupoverview_add_mapping($groupid, $categoryid);
            }
        }

        redirect($PAGE->url);
    } else if ($create) {

        // If create button was clicked, show the create form.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('creategroup', 'selfgroup'));
        $createform->display();
        echo $OUTPUT->footer();
    }
} else if ($canedit and $edit) {

    // Add form to edit existing group.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editgroup', 'selfgroup'));

    // Get old category of group to display in form.
    $oldcategories = array();
    $optionoldcategories = array();

    $i = 0;
    foreach ($allgroupoverviewinstances as $groupoverview) {
        $mappings = groupoverview_get_mappings($groupoverview->id);

        if (isset($mappings->mappings[$edit])) {
            $catid = $mappings->mappings[$edit];

            array_push($oldcategories, $catid);

            $cat = $DB->get_records('groupoverview_categories', array('id' => $catid, 'groupoverviewid' => $groupoverview->id));
            $categoryname = array_values($cat)[0]->name;

            for ($n = 0; $n < count($optiongroupoverviews[$i]); $n++) {

                if ($optiongroupoverviews[$i][$n] === $categoryname) {
                    array_push($optionoldcategories, $n);
                }
            }

            $i++;
        }
    }

    $data = array('id' => $cm->id, 'edit' => $edit, 'groupings' => $optiongroupings,
        'groupoverviews' => $optiongroupoverviews, 'oldcategories' => $optionoldcategories);
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $course->maxbytes,
        'trust' => false, 'context' => $context, 'noclean' => true);

    $mform = new editgroup_form(null, array($data, $selfgroup, $allgroups[$edit]));

    if ($mform->is_cancelled()) {

        redirect($PAGE->url);
    } else if ($dataformedit = $mform->get_data()) {

        $dataformedit->id = $edit;
        $dataformedit->courseid = $course->id;

        // Update existing group with new group information.
        groups_update_group($dataformedit, $mform, $editoroptions);

        // Update category for groupoverview or add mapping for group to category if set.
        for ($i = 0; $i < count($allgroupoverviews); $i++) {
            $a = 'overview' . $i;
            $cid = $dataformedit->$a;

            // If old category exists update mapping.
            if (isset($oldcategories[$i])) {
                $newcategoryid = $allgroupoverviews[$i][$cid];
                $oldcategoryid = $oldcategories[$i];

                groupoverview_update_mapping($edit, $oldcategoryid, $newcategoryid);
            } else {
                // Groupoverview was not present when group was created, add new mapping.
                if (isset($dataformedit->$a)) {
                    $cid = $dataformedit->$a;
                    $categoryid = $allgroupoverviews[$i][$cid];
                    $return = groupoverview_add_mapping($edit, $categoryid);
                    var_dump($return);
                }
            }
        }

        redirect($PAGE->url);
    } else {

        // If create button was clicked, show the edit form.
        $mform->display();
    }
    echo $OUTPUT->footer();
} else if ($unenroll and $canunenroll and isset($usergroups[$unenroll])) {

    // Unenroll from group if user can unroll and is member of this group.

    if ($confirm and data_submitted() and confirm_sesskey()) {

        // Remove member from group.
        groups_remove_member($unenroll, $USER->id);

        // Update completion state to incomplete.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $selfgroup->completionenabled) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE);
        }

        // If setting selected, delete empty group.
        if ($selfgroup->deleteemptygroups and !groups_get_members($unenroll, $USER->id)) {
            groups_delete_group($unenroll);
        }

        redirect($PAGE->url);
    } else {
        $groupname = format_string($usergroups [$unenroll]->name, true, array(
            'context' => $context
        ));

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('unenroll', 'selfgroup', $groupname));
        $confirmurl = new moodle_url ('/mod/selfgroup/view.php', array(
            'id' => $cm->id,
            'unenroll' => $unenroll,
            'confirm' => 1,
            'sesskey' => sesskey()
        ));
        $message = get_string('unenrollconfirm', 'selfgroup', $groupname);
        echo $OUTPUT->confirm($message, $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
    }
} else if ($enroll and $canenroll) {

    // Add form to enrol for group.

    $grouptoenrol = $allgroups[$enroll];
    $groupname = format_string($grouptoenrol->name, true, array(
        'context' => $context
    ));

    $usercount = isset ($counts [$enroll]) ? $counts [$enroll]->usercount : 0;
    $password = $allgroups[$enroll]->enrolmentkey;

    $data = array(
        'id' => $id,
        'enroll' => $enroll,
        'group_password' => $password
    );
    $enrollform = new enrollgroup_form (null, array(
        $data,
        $selfgroup,
        $groupname,
        $grouptoenrol
    ));

    if ($enrollform->is_cancelled()) {
        redirect($PAGE->url);
    }

    if ($selfgroup->maxmembers and $selfgroup->maxmembers <= $usercount) {
        $problems [] = get_string('cannotenrolmaxed', 'selfgroup', $groupname);

    } else if ($return = $enrollform->get_data()) {

        // Add member to group.
        groups_add_member($enroll, $USER->id);

        // Update completion state to complete.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $selfgroup->completionenabled) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }

        redirect($PAGE->url);

    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('enroll', 'selfgroup', $groupname));
        echo $OUTPUT->box_start('generalbox', 'notice');
        echo '<p>' . get_string('enrollconfirm', 'selfgroup', $groupname) . '</p>';
        $enrollform->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
    }

} else if ($isavailable) {

    // Show problem notifications.
    if ($problems) {
        foreach ($problems as $problem) {
            echo $OUTPUT->notification($problem, 'notifyproblem');
        }
    }

    // Output starts here.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($selfgroup->name);

    if ($selfgroup->intro) {
        echo $OUTPUT->box(format_module_intro('selfgroup', $selfgroup, $cm->id), 'generalbox mod_introbox', 'selfgroupintro');
    }

    // Notify teacher or admin that no groupings are defined in course.
    if ((count($allgroupings) <= 0) and $isteacheroradmin) {
        echo $OUTPUT->notification(get_string('nogroupingsdefined', 'selfgroup'));
    }

    // Notify teacher or admin that no groupoverview ressource activity is defined in course.
    if ((count($allgroupoverviewinstances) <= 0) and $isteacheroradmin) {
        echo $OUTPUT->notification(get_string('nogroupoverviewincourse', 'selfgroup'));
    }


    // Add create group button.
    if ($cancreate and $isavailable and !$create and (count($allgroupings) > 0)) {
        echo $OUTPUT->single_button(new moodle_url ('/mod/selfgroup/view.php', array(
            'id' => $cm->id,
            'create' => true
        )), get_string('creategroup', 'selfgroup'));
    }

    // Show all groups in table.
    $table = new html_table();
    $table->head = array(get_string('groupname', 'selfgroup'), get_string('grouppicture', 'selfgroup'),
        get_string('groupdescription', 'selfgroup'), get_string('groupmembercount', 'selfgroup'),
        get_string('groupmembers', 'selfgroup'), get_string('actions', 'selfgroup'));

    if ($allgroups = groups_get_all_groups($course->id, 0)) {

        $i = 1;
        foreach ($allgroups as $group) {

            $ismember = isset ($usergroups [$group->id]);

            // Add group picture.
            $cellpicture = new html_table_cell(print_group_picture($group, $course->id, true, true, false));

            // Add member count.
            $membercount = 0;
            if (isset($membercounts[$group->id])) {
                $membercount = $membercounts [$group->id]->usercount;
            }
            if ($selfgroup->maxmembers) {
                $cellmembercount = new html_table_cell($membercount . '/' . $selfgroup->maxmembers);
            } else {
                $cellmembercount = $membercount;
            }

            // Add names of gorup members.
            $membernames = array();
            if ($members = groups_get_members($group->id)) {
                foreach ($members as $member) {
                    $membernames [] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                        $member->id . '&amp;course=' . $course->id . '">' . fullname($member) . '</a>';
                }
            }

            // Add actions available to the user.
            $actionsstring = '';

            // Add action link to unenrol from group.
            if ($ismember and $canunenroll) {
                $actionsstring = $actionsstring . '<a href="' . new moodle_url ('/mod/selfgroup/view.php', array(
                        'id' => $cm->id,
                        'unenroll' => $group->id
                    )) . '">' . get_string('unenroll', 'selfgroup') . '</a></br>';
            }

            // Add action link to enrol for group.
            if ($canenroll and ($selfgroup->maxmembers > $membercount)) {
                $actionsstring = $actionsstring . '<a href="' . new moodle_url('/mod/selfgroup/view.php', array(
                        'id' => $cm->id,
                        'enroll' => $group->id
                    )) . '">' . get_string('enroll', 'selfgroup') . '</a></br>';
            }

            // Add action link to edit group information.
            if ($canedit and $ismember) {
                $actionsstring = $actionsstring . '<a href="' . new moodle_url('/mod/selfgroup/view.php', array(
                        'id' => $cm->id,
                        'edit' => $group->id
                    )) . '">' . get_string('editgroup', 'selfgroup') . '</a></br>';
            }

            $actions = new html_table_cell($actionsstring);

            // Add all data to the table.
            $row = new html_table_row(array($group->name, $cellpicture, $group->description,
                $cellmembercount, implode(', ', $membernames), $actions));
            $row->attributes['data-id'] = $i;
            $row->attributes['data-parentid'] = $i - 1;
            $table->data[] = $row;
        }
    } else {

        // Add notification that there are no groups to display.
        echo $OUTPUT->notification(get_string('nogroups', 'selfgroup'));
    }

    echo html_writer::table($table);
    // Finish the page.
    echo $OUTPUT->footer();
} else {

    // If the enrollment for groups has not yet started.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($selfgroup->name);
    echo $OUTPUT->notification(get_string('activitynotavailable', 'selfgroup', userdate($selfgroup->timestartenrollment)));
    // Finish the page.
    echo $OUTPUT->footer();
}
