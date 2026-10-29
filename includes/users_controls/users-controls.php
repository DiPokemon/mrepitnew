<?php
if (!defined('ABSPATH')) exit;

define('SCHOOL_USERS_CONTROLS_PATH', __DIR__);

require_once SCHOOL_USERS_CONTROLS_PATH . '/common/capabilities.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/security.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/helpers.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/teacher-link.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/sync.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/admin-menu.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/common/carbon-fields.php';

// Parents
require_once SCHOOL_USERS_CONTROLS_PATH . '/parents_controls/list-table.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/parents_controls/pages.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/parents_controls/handlers.php';

// Students
require_once SCHOOL_USERS_CONTROLS_PATH . '/students_controls/list-table.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/students_controls/pages.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/students_controls/handlers.php';

// Teachers
require_once SCHOOL_USERS_CONTROLS_PATH . '/teachers_controls/list-table.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/teachers_controls/pages.php';
require_once SCHOOL_USERS_CONTROLS_PATH . '/teachers_controls/handlers.php';
