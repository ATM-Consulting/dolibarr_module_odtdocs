<?php

	if(is_file('../main.inc.php'))$dir = '../';
	else  if(is_file('../../../main.inc.php'))$dir = '../../../';
	else  if(is_file('../../../../main.inc.php'))$dir = '../../../../';
	else  if(is_file('../../../../../main.inc.php'))$dir = '../../../../../';
	else $dir = '../../';

	if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
		include($dir."master.inc.php");
	}
	elseif(!defined('INC_FROM_DOLIBARR')) {
		include($dir."main.inc.php");
	} else {
		global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass;
	}
	if(!defined('DB_HOST') && !empty($dolibarr_main_db_host)) {
		define('DB_HOST',$dolibarr_main_db_host);
		define('DB_NAME',$dolibarr_main_db_name);
		define('DB_USER',$dolibarr_main_db_user);
		define('DB_PASS',$dolibarr_main_db_pass);
		define('DB_DRIVER',$dolibarr_main_db_type);
	}

	dol_include_once('/abricot/inc.core.php');
	

define('DOL_ADMIN_USER','admin');

//define('USE_ONLINE_SERVICE','http://pdfservice.atm-consulting.fr/pdf.php');

define('PATH_TO_LIBREOFFICE', 'libreoffice');
//define('PATH_TO_LIBREOFFICE', '"C:\Program Files (x86)\LibreOffice 4.0\program\soffice.exe"');

define('CMD_CONVERT_TO_PDF', PATH_TO_LIBREOFFICE.' --invisible --norestore --headless --convert-to pdf --outdir');
