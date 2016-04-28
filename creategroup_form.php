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

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Module instance settings form
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class creategroup_form extends moodleform {

    /**
     * @var stdClass $selfgroup needed for holding information about the selfgroup instance
     */
    private $selfgroup;

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        list ($data, $this->selfgroup) = $this->_customdata;

        $groupings = $data['groupings'];
        $groupoverviews = $data['groupoverviews'];

        // Add editoroptions for group description.
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => 1024, 'trust' => false, 'noclean' => true);

        // Add the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('groupname', 'selfgroup'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'groupname', 'selfgroup');

        // Add editor for providing group information.
        $mform->addElement('editor', 'description_editor', get_string('groupdescription', 'group'), $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'groupdescription', 'selfgroup');

        // Add textfield for link to repository.
        $mform->addElement('text', 'link', get_string('linkrepository', 'selfgroup'), array('size' => '64'));
        $mform->addHelpButton('link', 'linkrepository', 'selfgroup');
        $mform->setType('link', PARAM_RAW);

        // Add enrolmentkey for group.
        $mform->addElement('passwordunmask', 'enrolmentkey', get_string('enrolmentkey', 'group'), 'maxlength="254" size="24"',
            get_string('enrolmentkey', 'group'));
        $mform->addHelpButton('enrolmentkey', 'enrolmentkey', 'group');
        $mform->setType('enrolmentkey', PARAM_RAW);

        // Add group picture for group.
        $mform->addElement('filepicker', 'imagefile', get_string('newpicture', 'group'));
        $mform->addHelpButton('imagefile', 'newpicture', 'group');

        // Add the "additional" fieldset for further information.
        $mform->addElement('header', 'additional', get_string('additionalinformation', 'selfgroup'));

        // Add select grouping to add group to.
        if (count($groupings) > 0) {
            $mform->addElement('select', 'grouping', get_string('selectgrouping', 'selfgroup'), $groupings);
        }

        // Add select category to add group to.
        if (count($groupoverviews) > 0) {
            $i = 0;
            foreach ($groupoverviews as $overview) {
                $mform->addElement('select', 'overview' . $i, get_string('selectgroupoverview', 'selfgroup'), $overview);
                $i++;
            }
        }

        // Add some hidden field information.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'create');
        $mform->setType('create', PARAM_BOOL);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $this->set_data($data);
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array $errors An array of errors
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        // Check password for password policy.
        if ($data['enrolmentkey'] != '') {
            $errmsg = '';

            if (!check_password_policy($data['enrolmentkey'], $errmsg)) {
                $errors['enrolmentkey'] = $errmsg;
            }
        }
        return $errors;
    }


}
