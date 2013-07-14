<?php

/**
 * This file is main route into the framework
 * @author Addshore
 **/

//Set the include path
$IP = dirname(__FILE__) . '/';

//Include all files in /includes
include $IP . 'includes/Globals.php';
include $IP . 'includes/General.php';
include $IP . 'includes/Registry.php';
include $IP . 'includes/Http.php';
include $IP . 'includes/Family.php';
include $IP . 'includes/Site.php';
include $IP . 'includes/ApiResult.php';
include $IP . 'includes/SiteFactory.php';
include $IP . 'includes/User.php';
include $IP . 'includes/UserLogin.php';
include $IP . 'includes/Page.php';
include $IP . 'includes/Category.php';
include $IP . 'includes/WikibaseEntity.php';
include $IP . 'includes/Mysql.php';
include $IP . 'includes/WikitextParser.php';

//Factory for creating sites
Globals::$Sites = new SiteFactory();