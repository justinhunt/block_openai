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
 * This file keeps track of upgrades to
 * the forum module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   block_openai
 * @copyright 2020 Justin Hunt (poodllsupport@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \block_openai\constants;

function xmldb_block_openai_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.


    if ($oldversion < 2022052100) {

        $table = new xmldb_table(constants::M_TABLE_FILES);
        $fields=[];
        //$fields[] = new xmldb_field('billinginterval', XMLDB_TYPE_INTEGER, '4', null, null, null, 0);
        //$fields[] = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $fields[] = new xmldb_field('stepsequence',
            XMLDB_TYPE_CHAR, '255', null, null, null, '0');
        $fields[] = new xmldb_field('seperator',
            XMLDB_TYPE_CHAR, '255', null, null, null, '0');
        $fields[] = new xmldb_field('exampleprompt',
            XMLDB_TYPE_TEXT, null, null, null, null, null);

        foreach($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // savepoint reached.
        upgrade_plugin_savepoint(true, 2020091500, 'block',constants::M_NAME);

    }



    return true;
}
