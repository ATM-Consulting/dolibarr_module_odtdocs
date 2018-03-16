<?php 

//define('USE_ONLINE_SERVICE','http://pdfservice.atm-consulting.fr/pdf.php');
//define('PATH_TO_LIBREOFFICE', '"C:\Program Files (x86)\LibreOffice 4.0\program\soffice.exe"');

if(!defined('PATH_TO_LIBREOFFICE')) define('PATH_TO_LIBREOFFICE', 'libreoffice');
if(!defined('CMD_CONVERT_TO_PDF')) define('CMD_CONVERT_TO_PDF', PATH_TO_LIBREOFFICE.' --invisible --norestore --headless --convert-to pdf --outdir');