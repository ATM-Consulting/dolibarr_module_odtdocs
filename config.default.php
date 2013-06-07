<?php

	define('ROOT','/var/www/dolibarr/htdocs/');
	define('COREROOT','/var/www/ATM/atm-core/');
	define('COREHTTP','http://127.0.0.1/ATM/atm-core/');
	define('HTTP','http://localhost/dolibarr/');

	if(defined('INC_FROM_CRON_SCRIPT')) {
		require_once(ROOT."master.inc.php");
	}
	else {
		require_once(ROOT."main.inc.php");
	}

	define('DB_HOST',$dolibarr_main_db_host);
	define('DB_NAME',$dolibarr_main_db_name);
	define('DB_USER',$dolibarr_main_db_user);
	define('DB_PASS',$dolibarr_main_db_pass);
	define('DB_DRIVER','mysqli');

	define('DOL_PACKAGE', true);
	define('USE_TBS', true);

	require(COREROOT.'inc.core.php');

	define('DOL_ADMIN_USER','admin');
