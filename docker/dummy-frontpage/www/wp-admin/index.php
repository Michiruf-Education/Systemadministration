<?php
    error_reporting(E_ALL);
	require_once('db.class.php');
	require_once('template.class.php');
	$db = new DB('db', 'root', 'P2VER3usSoD6dkWoHlQG', 'honeypot', false);
	$tpl = new Template('honeypot.html');
	if (isset($_POST['log']) && isset($_POST['pwd'])) {
	    $db->query('INSERT INTO `honeypot` (name,password,ip,agent) VALUES ("'.$db->sanitize($_POST['log']).'", "'.$db->sanitize($_POST['pwd']).'", "'.$db->sanitize($_SERVER['HTTP_X_FORWARDED_FOR']).'", "'.$db->sanitize($_SERVER['HTTP_USER_AGENT']).'");');
	    $tpl->assign('error', 'ERROR: Invalid username.');
	} else {
	    $tpl->assign('error', '');
	}
	echo $tpl->get();
?>