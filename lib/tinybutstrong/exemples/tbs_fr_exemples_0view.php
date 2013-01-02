<?php
if (!isset($_GET)) $_GET=&$HTTP_GET_VARS;
$x = $_GET['script'];
$x = str_replace(array('/','\\','..',"\r","\n",':',';','!','@',"'",'"','~','&'),'',$x); // prevent from hacking
$x = 'tbs_fr_exemples_'.$x; // prevent from hacking
$ishtml = (substr($x,-4)!='.php');
if (file_exists($x)) echo f_color_file($x, $ishtml, true);
exit;

function f_color_file($file, $ishtml, $lines) {

	$x = highlight_file($file, true);

	if ($ishtml) {
		f_color_tag($x, 't', 'table,tr,td,th');
		f_color_tag($x, 's', 'script');
		f_color_tag($x, 'c', 'style');
		f_color_tag($x, 'n');
	}
	
	if ($lines) {
		// display line number
		$n = 1 + substr_count($x, '<br />');
		$n_txt = '';
		for ($i=1;$i<=$n;$i++) {
			if ($i>1) $n_txt .= "\r\n";
			$n_txt .= $i; 
		}
		$x = '<div style="position:absolute; left:0; top:0; width:30px; text-align:right; color: #666; border-right: #666 1px solid; padding-right: 2px;"><pre class="z">'.$n_txt.'</pre></div><div class="z" style="position:absolute; top:0; left:40px;">'.$x.'</div>';
	}

	$style = '.z {font-family: monospace;	font-size: 12px; margin:0; padding:0;} '."\r\n";
	if ($lines)  $style .= '.n {color: #009;} .t {color: #099;} .v {color: #00F;} .s {color: #900;} .c {color: #909;} '."\r\n";
	$x = '<style type="text/css"><!-- '."\r\n".$style.' --></style>'."\r\n".$x;

	return $x;
}

function f_color_tag(&$txt, $class, $tag='') {
// color a list of tags or all remaing tags with using a CSS class.
// $txt must be a source code wich is a result of highlight_file().

	$z2 = '<span class="'.$class.'">';
	$zo = '&lt;';
	$zc = '&gt;';
	$zc_len = strlen('&gt;');
	
	$all = ($tag===''); // color all remaing tags

	if ($all) $txt = str_replace($zc,$zc.'</span>',$txt);

	if (is_string($tag)) $tag = explode(',', $tag);
	foreach ($tag as $t) {
		$p = 0;
		$z = $zo.$t;
		$z_len = strlen($z);
		do {
			$p = strpos($txt, $z, $p);
			if ($p!==false) {
				if ($all or (substr($txt,$p+$z_len,1)==='&')) { // the next char must be a ' ' or a '>'. In both case, it is converted by highlight_file() with a special char begining with '&'.
					if (($p>0) and (substr($txt,$p-2,2)!=='">')) { // the tag must not be previsouly colored 
						$p2 = strpos($txt, $zc, $p+$z_len);
						if ($p2!==false) {
							$x = substr($txt, $p, $p2 + $zc_len - $p);
							$x = str_replace('="','=<span class="v">"',$x); // color the value of attributes
							$x = str_replace('=\'','=<span class="v">\'',$x); // color the value of attributes
							$x = str_replace('"&','"</span>&',$x);
							$x = $z2.$x.'</span>';
							$txt = substr($txt,0,$p).$x.substr($txt,$p2 + $zc_len);
							$p = $p + strlen($x);
						} else {
							$p = false;
						}
					} else {
						$p = $p + $z_len;
					}
				} else {
					$p = $p + $z_len;
				}
			}
		} while ($p!==false);
		$z = $zo.'/'.$t.$zc;
		$txt = str_replace($z,$z2.$z.'</span>',$txt);
	}
	
}

?>