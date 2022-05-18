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
 * Event observer for block_openai plugin
 *
 * @package    block_openai
 * @copyright  Justin Hunt (https://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_openai;

defined('MOODLE_INTERNAL') || die();

class event_observer{


    /**
     * Triggered via user_updated event.
     *
     * @param \core\event\user_updated $event
     * @return bool true on success
     */
    public static function user_updated(\core\event\user_updated $event) {
        $maxusers = get_config(constants::M_COMP,'maximumusers');
        /*
        $activeusercount = common::fetch_active_user_count();
        if($activeusercount >= $maxusers ){
            if(common::can_create_users()){
                common::disable_user_creations();
            }
        }elseif($activeusercount < $maxusers ){
            if(!common::can_create_users()){
                common::enable_user_creations();
            }
        }
        */
        return true;
    }

    /**
     * Triggered via user_deleted event.
     *
     * @param \core\event\user_deleted $event
     * @return bool true on success
     */
    public static function user_created(\core\event\user_created $event) {
        $maxusers = get_config(constants::M_COMP,'maximumusers');
        /*
        if(common::fetch_active_user_count() >= $maxusers ){
            if(common::can_create_users()){
                common::disable_user_creations();
            }
        }
        */
        return true;
    }

    /**
     * Triggered via user_deleted event.
     *
     * @param \core\event\user_deleted $event
     * @return bool true on success
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        $maxusers = get_config(constants::M_COMP,'maximumusers');
        /*
        if(common::fetch_active_user_count() < $maxusers ){
            if(!common::can_create_users()){
                common::enable_user_creations();
            }
        }
        */
        return true;
    }

    /**
     * Triggered via course_created event.
     *
     * @param \core\event\course_created $event
     * @return bool true on success
     */
    public static function course_created(\core\event\course_created $event) {
        $maxcourses = get_config(constants::M_COMP,'maximumcourses');
        /*
        $activecoursecount = common::fetch_active_course_count();
        if($activecoursecount >= $maxcourses ){
            if(common::can_create_courses()){
                common::disable_course_creations();
            }
        }
        */
        return true;
    }

    /**
     * Triggered via course_updated event.
     *
     * @param \core\event\course_updated $event
     * @return bool true on success
     */
    public static function course_updated(\core\event\course_updated $event) {
        $maxcourses = get_config(constants::M_COMP,'maximumcourses');
        /*
        $activecoursecount = common::fetch_active_course_count();
        if($activecoursecount >= $maxcourses ){
            if(common::can_create_courses()){
                common::disable_course_creations();
            }
        }elseif($activecoursecount < $maxcourses ){
            if(!common::can_create_courses()){
                common::enable_course_creations();
            }
        }
        */
        return true;
    }

    /**
     * Triggered via course_deleted event.
     *
     * @param \core\event\course_deleted $event
     * @return bool true on success
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        $maxcourses = get_config(constants::M_COMP,'maximumcourses');
        /*
        $activecoursecount = common::fetch_active_course_count();
        if($activecoursecount < $maxcourses ){
            if(!common::can_create_courses()){
                common::enable_course_creations();
            }
        }
        */
        return true;
	}

}
