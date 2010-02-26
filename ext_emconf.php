<?php

########################################################################
# Extension Manager/Repository config file for ext "rediscache".
#
# Auto generated 26-02-2010 16:27
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Redis cache backend',
	'description' => 'A Redis cache backend implementation for the TYPO3 4.3 cache backend.',
	'category' => 'services',
	'author' => 'Christopher Hlubek',
	'author_email' => 'hlubek@networkteam.com',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"220d";s:10:"README.txt";s:4:"1022";s:16:"ext_autoload.php";s:4:"1778";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"9af5";s:58:"Classes/class.tx_rediscache_cache_backend_RedisBackend.php";s:4:"c667";s:19:"doc/wizard_form.dat";s:4:"d764";s:20:"doc/wizard_form.html";s:4:"ff43";}',
	'suggests' => array(
	),
);

?>