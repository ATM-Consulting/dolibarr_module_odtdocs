<?php

//define('ROOT','/var/www/dolibarr/htdocs/');
define('ROOT','/var/www/html/client/latoxan/dolibarr/htdocs/');
//define('COREROOT','/var/www/ATM/atm-core/');
define('COREROOT','/var/www/html/client/latoxan/dolibarr/htdocs/custom/core/');
//define('COREHTTP','http://127.0.0.1/ATM/atm-core/');
define('COREHTTP','https://latoxan.srv40.atm-consulting.fr/custom/core/');
//define('HTTP','http://localhost/dolibarr/');
define('HTTP','https://latoxan.srv40.atm-consulting.fr');

if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
	include(ROOT."master.inc.php");
}
elseif(!defined('INC_FROM_DOLIBARR')) {
	include(ROOT."main.inc.php");
} else {
	global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass;
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

//define('USE_ONLINE_SERVICE','http://pdfservice.atm-consulting.fr/pdf.php');

define('PATH_TO_LIBREOFFICE', 'libreoffice');
//define('PATH_TO_LIBREOFFICE', '"C:\Program Files (x86)\LibreOffice 4.0\program\soffice.exe"');

define('CMD_CONVERT_TO_PDF', PATH_TO_LIBREOFFICE.' --invisible --norestore --headless --convert-to pdf --outdir');
