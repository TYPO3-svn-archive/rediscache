Redis cache
===============================================================================================

Redis is an interesting low dependency key-value store with network interface. In addition to
storing values in keys like Memcache, Redis acts as a data structure service with features for
atomic list and set manipulation with many constant (or linear) time operations. This makes
tag management for the cache framework simple and fast.

Configure the Redis cache backend in localconf.php to use the backend as a replacement for the
default database cache:

 
  _______________
 / localconf.php \
-----------------------------------------------------------------------------------------------
$TYPO3_CONF_VARS['SYS']['useCachingFramework'] = '1';

$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['cache_hash'] = array(
	'backend' => 'tx_rediscache_cache_backend_RedisBackend',
	'options' => array(
		'identifierPrefix' => 'typo3.local-cache_hash%',
		'hostname' => '127.0.0.1',
		'port' => 6379
	)
);
$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['cache_pages'] = array(
	'backend' => 'tx_rediscache_cache_backend_RedisBackend',
	'options' => array(
		'identifierPrefix' => 'typo3.local-cache_pages%'
	)
);
$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['cache_pagesection'] = array(
	'backend' => 'tx_rediscache_cache_backend_RedisBackend',
	'options' => array(
		'identifierPrefix' => 'typo3.local-cache_pagesection%'
	)
);
-----------------------------------------------------------------------------------------------
