<?php

/**
 * Plugin Name: Updraft at three
 * Description: Schedules the time of the Backup for 3AM
 * Version: 1.0
 * Requires at least: 5.6
 * Tested up to: 5.7
 * Requires PHP: 7.4
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * Text Domain: wctt
 * Domain Path: /languages
 * License: GPLv2 or later
 */

namespace UpdraftAtThree;

add_filter('updraftplus_schedule_firsttime_db', '\\UpdraftAtThree\\schedule_firsttime_filter');
add_filter('updraftplus_schedule_firsttime_files', '\\UpdraftAtThree\\schedule_firsttime_filter');


/**
 * Filters the first scheduled time for UpdraftPlus backups.
 *
 * This function determines and returns the timestamp for the next 3 AM based on the current time.
 * If the current hour is 3 or later, it schedules for the following day's 3 AM; otherwise, it schedules for today's 3 AM.
 *
 * @param int $schedule The current schedule timestamp (unused).
 * @return int The timestamp for the next 3 AM.
 */

function schedule_firsttime_filter($schedule)
{
    $current_hour = current_time('G');
    // Determine the date of the next 3 AM
    $next_3_am_date = $current_hour >= 3 ? strtotime('tomorrow 3:00') : strtotime('today 3:00');

    return $next_3_am_date;
}
