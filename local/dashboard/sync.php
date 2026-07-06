<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * On-demand refresh of cached dashboard data for the current company.
 *
 * @package   local_dashboard
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/local/index_snapshot.php');

require_login();

$r = local_dashboard_bootstrap_report();
if ($r === null) {
    exit;
}

require_sesskey();

$companycontext = $r->companycontext;
local_dashboard_require_dashboard_view((int) $r->companyid, $companycontext);

\local_dashboard\local\index_snapshot::refresh_company((int) $r->companyid);

$params = local_dashboard_filter_url_params($r);
$params['dashboardsynced'] = 1;
redirect(new moodle_url('/local/dashboard/index.php', $params));
