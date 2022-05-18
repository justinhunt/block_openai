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
 *
 *
 * @package    block_openai
 * @copyright  2022 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_openai\task;

defined('MOODLE_INTERNAL') || die();

use \block_openai\constants;
use \block_openai\common;

/**
 * A block_openai adhoc task to create unassigned tenants
 *
 * @package    block_openai
 * @since      Moodle 4.0
 * @copyright  2022 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openai_createtenants extends \core\task\adhoc_task {

    /**
     *  Run the tasks
     */
    public function execute() {
        global $DB;
        $trace = new \text_progress_trace();
        $cd = $this->get_custom_data();
       // common::create_tenants($cd->tenantcount,$cd->tenantjobno,$trace);
    }

    protected function do_retry($reason, $trace, $customdata) {
        if($customdata->taskcreationtime + (HOURSECS * 24) < time()){
            //after 24 hours we give up
            $trace->output($reason . ": Its been more than 24 hours. Giving up on this transcript.");
            return;

        }elseif ($customdata->taskcreationtime + (MINSECS * 15) < time()) {
            //15 minute delay
            $delay = (MINSECS * 15);
        }else{
            //30 second delay
            $delay = 30;
        }
        $trace->output($reason . ": will try again next cron after $delay seconds");
        //$modelaudio_task = new \block_openai\task\openai_template_generate();
        $modelaudio_task->set_component(constants::M_COMP);
        $modelaudio_task->set_custom_data($customdata);
        //if we do not set the next run time it can extend the current cron job indef with a recurring task
        $modelaudio_task->set_next_run_time(time()+$delay);
        // queue it
        \core\task\manager::queue_adhoc_task($modelaudio_task);
    }

    protected function do_forever_fail($reason, $trace) {
        $trace->output($reason . ": will not retry ");
    }

}

