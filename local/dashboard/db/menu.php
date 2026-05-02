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
 * Menu entries for local_dashboard.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define menu items.
 *
 * @return array
 */
function local_dashboard_menu(): array {
    return [
        'dashboard' => [
            'category' => 'Reports',
            'tab' => 1,
            'name' => get_string('pluginname', 'local_dashboard'),
            'url' => '/local/dashboard/index.php',
            'cap' => 'local/dashboard:view',
            'icondefault' => 'report',
            'style' => 'report',
            'icon' => 'fa-tachometer',
            'iconsmall' => 'fa-line-chart',
        ],
    ];
}
