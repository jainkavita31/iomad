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
 * @package   theme_space
 * @copyright 2022 onwards, Marcin Czaja (https://rosea.io)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */


defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('theme_space_settingswcag', get_string('settingswcag', 'theme_space'));

$name = 'theme_space/hwcag';
$heading = get_string('hwcag', 'theme_space', $a);
$setting = new space_setting_specialsettingheading($name, $heading,
    format_text(get_string('hwcag_desc', 'theme_space', $a), FORMAT_MARKDOWN));
$page->add($setting);

$name = 'theme_space/wcagskiplinks';
$title = get_string('wcagskiplinks', 'theme_space');
$description = get_string('wcagskiplinks_desc', 'theme_space');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

// 0. Skip Links Description.
$name = 'theme_space/wcagskiplinksdescription';
$title = get_string('wcagskiplinksdescription', 'theme_space');
$description = get_string('wcagskiplinksdescription_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// 1. Sidebar Navigation.
$name = 'theme_space/wcagsidenav';
$title = get_string('wcagsidenav', 'theme_space');
$description = get_string('wcagsidenav_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// 2. Mobile Navigation.
$name = 'theme_space/wcagmobilenav';
$title = get_string('wcagmobilenav', 'theme_space');
$description = get_string('wcagmobilenav_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// 3. Top Bar Navigation.
$name = 'theme_space/wcagtopbarnav';
$title = get_string('wcagtopbarnav', 'theme_space');
$description = get_string('wcagtopbarnav_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// 4. Sidebar Blocks.
$name = 'theme_space/wcagblockssidebar';
$title = get_string('wcagblockssidebar', 'theme_space');
$description = get_string('wcagblockssidebar_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);

// 5. Page Footer.
$name = 'theme_space/wcagpagefooter';
$title = get_string('wcagpagefooter', 'theme_space');
$description = get_string('wcagpagefooter_desc', 'theme_space');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$page->add($setting);


// Must add the page after definiting all the settings!
$settings->add($page);
