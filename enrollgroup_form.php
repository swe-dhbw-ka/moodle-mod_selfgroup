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
require_once($CFG->dirroot . '/lib/password_compat/lib/password.php');

/**
 * Module instance settings form
 *
 * @package    mod_selfgroup
 * @copyright  2016 Julia Anken
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrollgroup_form extends moodleform {

    /**
     * @var stdClass $selfgroup needed for holding information about the selfgroup instance
     */
    private $selfgroup;

    /**
     * @var stdClass $grouptoenrol needed for holding information about the group to enrol for
     */
    private $grouptoenrol;

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        list ($data, $this->selfgroup, $groupname, $this->grouptoenrol) = $this->_customdata;

        if ($data ['group_password']) {
            $mform->addElement('passwordunmask', 'password', get_string('password', 'selfgroup'), 'maxlength="254" size="24"');
            $mform->setType('password', PARAM_RAW);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'enroll');
        $mform->setType('enroll', PARAM_INT);

        $mform->addElement('hidden', 'group_password');
        $mform->setType('group_password', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('enroll', 'selfgroup', $groupname));

        $this->set_data($data);
    }

    /**
     * Validation of the data provided by the user
     *
     * @param array $data
     * @param array $files
     * @return array $errors An array of errors
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data ['group_password']) {

            $password = $this->grouptoenrol->enrolmentkey;
            $passwordhash = password_hash($password, PASSWORD_DEFAULT);

            if (!password_verify($data ['password'], $passwordhash)) {
                $errors ['password'] = get_string('incorrectpassword', 'selfgroup');
            }

        }
        return $errors;
    }
}