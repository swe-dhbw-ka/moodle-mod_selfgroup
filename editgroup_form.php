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
class editgroup_form extends moodleform {

    /**
     * @var stdClass $selfgroup needed for holding information about the selfgroup instance
     */
    private $selfgroup;

    /**
     * @var stdClass $editgroup needed for holding information about the group to edit
     */
    private $editgroup;

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        list ($data, $this->selfgroup, $this->editgroup) = $this->_customdata;

        $groupoverviews = $data['groupoverviews'];
        $oldcategories = $data['oldcategories'];

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

        $mform->setDefault('name', $this->editgroup->name);

        // Add editor for group description.
        $mform->addElement('editor', 'description_editor', get_string('groupdescription', 'group'), $editoroptions)->setValue(
            array('text' => $this->editgroup->description));
        $mform->setType('description_editor', PARAM_RAW);

        // Add group picture.
        $mform->addElement('filepicker', 'imagefile', get_string('newpicture', 'group'));
        $mform->addHelpButton('imagefile', 'newpicture', 'group');

        // Add select category to add group to.
        if (count($groupoverviews) > 0) {

            // Add the "additional" fieldset for further information.
            $mform->addElement('header', 'additional', get_string('additionalinformation', 'selfgroup'));

            $i = 0;
            foreach ($groupoverviews as $overview) {
                $selectcategory = $mform->addElement('select', 'overview' . $i,
                    get_string('selectgroupoverview', 'selfgroup'), $overview);

                if (isset($oldcategories[$i])) {
                    $selectcategory->setSelected($oldcategories[$i]);
                }
                $i++;
            }
        }

        // Add some hidden field information.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $this->set_data($data);
    }

}
