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
 * The main selfgroup configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_selfgroup_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Add the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('selfgroupname', 'selfgroup'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // Add the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Define minimal groupsize -> Default 0.
        $mform->addElement('text', 'minmembers', get_string('minmembers', 'selfgroup'), array('size' => '4'));
        $mform->setType('minmembers', PARAM_INT);
        $mform->setDefault('minmembers', 0);

        // Define maximum groupsize -> Default 5.
        $mform->addElement('text', 'maxmembers', get_string('maxmembers', 'selfgroup', array('size' => '4')));
        $mform->setType('maxmembers', PARAM_INT);
        $mform->setDefault('maxmembers', 5);

        // Add date for start of enrollment.
        $mform->addElement('date_time_selector', 'timestartenrollment', get_string('timestartenrollment', 'selfgroup'), array(
            'optional' => true,
            'startyear' => 2010,
            'stopyear' => 2025,
            'timezone' => 99,
            'step' => 5
        ));
        $mform->setDefault('timestartenrollment', 0);

        // Add checkbox for setting: delete group when no members enrolled.
        $mform->addElement('advcheckbox', 'deleteemptygroups', get_string('deleteemptygroups', 'selfgroup'), '',
            array('group' => 1), array(0, 1));
        $mform->setDefault('deleteemptygroups', true);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Adding elements to the form for custom rules in activity completion
     *
     * @return array  array containing checkbox to enable activity completion
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completionenabled', ' ', get_string('completiongroupmember', 'selfgroup'));
        $mform->addGroup($group, 'completiongroupmember', get_string('completiongroupmember', 'selfgroup'), array(' '), false);

        return array('completiongroupmember');

    }

    /**
     * Check whether the custom rule for activity completion is enabled
     *
     * @param $data data provided by the user to the form
     * @return bool true if the custom rule for activity completion is enabled
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionenabled']));
    }
}
