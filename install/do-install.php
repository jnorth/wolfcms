<?php

/**
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009 Martijn van der Kleijn <martijn.niji@gmail.com>
 *
 * This file is part of Wolf CMS.
 *
 * Wolf CMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Wolf CMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Wolf CMS.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Wolf CMS has made an exception to the GNU General Public License for plugins.
 * See exception.txt for details and the full text.
 */

/* Make sure we've been called using index.php */
if (!defined('INSTALL_SEQUENCE')) {
    echo '<p>Illegal call. Terminating.</p>';
    exit();
}

require 'Template.php';

$msg = '';
$PDO = false;

// Setup default admin user name in case admin username is not entered in install screen
$admin_name = DEFAULT_ADMIN_USER;

// Create config.php template
$config_tmpl = new Template('config.tmpl');
$config_tmpl->assign($config);

// Get generated config.php
$config_content = $config_tmpl->fetch();

// Write config.php
if (!file_put_contents(CFG_FILE, $config_content)) {
    $error .= "<ul><li><strong>Config file could not be written!</strong></li>\n";
}
else {
    $msg .= "<ul><li>Config file successfully written.</li>\n";
}

// Include generated config.php
require CFG_FILE;

// Generate admin name (defaults to 'admin') and pwd
if (isset($_POST['config']['admin_username'])) {
    $admin_name = $_POST['config']['admin_username'];
    $admin_name = trim($admin_name);

    try {
        $admin_passwd_precrypt = '12'.dechex(rand(100000000, 4294967295)).'K';
        $admin_passwd = sha1($admin_passwd_precrypt);
    } catch (Exception $e) {
        $error = 'Wolf CMS could not generate a default administration password and has not been installed.<br />The following error has occured: <p><strong>'. $e->getMessage() ."</strong></p>\n";
        file_put_contents(CFG_FILE, '');
    }
}

// Try creating a new PDO object to connect to DB
try {
    $PDO = new PDO(DB_DSN, DB_USER, DB_PASS);
} catch (PDOException $e) {
    $error = 'Wolf CMS could not connect to the database and has not been installed.<br />The following error has occured: <p><strong>'. $e->getMessage() ."</strong></p>\n";
    file_put_contents(CFG_FILE, '');
}

// Run the SQL to setup DB contents
if ($PDO) {
    $msg .= '<li>Database connection successfull.</li>';

    try {
        require_once 'schema_'.$_POST['config']['db_driver'].'.php';
    }
    catch (Exception $e) {
        $error = 'Wolf CMS could not create the database schema and has not been installed properly.<br />The following error has occured: <p><strong>'. $e->getMessage() ."</strong></p>\n";
    }

    try {
        require_once 'sql_data.php';
    }
    catch (Exception $e) {
        $error = 'Wolf CMS could not create the default database contents and has not been installed properly.<br />The following error has occured: <p><strong>'. $e->getMessage() ."</strong></p>\n";
    }

    $msg .= '<li>Tables loaded successfully</li></ul>
             <p>You can now login at <a href="../admin/">the login page</a> with: </p>
             <p>
                <strong>Login</strong> - '.$admin_name.'<br />
                <strong>Password <sup>1)</sup></strong> - '.$admin_passwd_precrypt.'
             </p>
            ';
}
else {
    $error = 'Wolf CMS could not connect to the database and was unable to create its database tables!';
}

?>