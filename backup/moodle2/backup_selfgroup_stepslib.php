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
 * Define all the backup steps that will be used by the backup_selfgroup_activity_task
 *
 * @package   mod_selfgroup
 * @category  backup
 * @copyright 2016 Julia Anken <juliaanken.development@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete selfgroup structure for backup, with file and id annotations
 *
 * @package   mod_selfgroup
 * @category  backup
 * @copyright 2016 Julia Anken <juliaanken.development@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_selfgroup_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the selfgroup instance.
        $selfgroup = new backup_nested_element('selfgroup', array('id'), array(
            'name', 'intro', 'introformat'));

        // If we had more elements, we would build the tree here.

        // Define data sources.
        $selfgroup->set_source_table('selfgroup', array('id' => backup::VAR_ACTIVITYID));

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

        // Define file annotations (we do not use itemid in this example).
        $selfgroup->annotate_files('mod_selfgroup', 'intro', null);

        // Return the root element (selfgroup), wrapped into standard activity structure.
        return $this->prepare_activity_structure($selfgroup);
    }
}
