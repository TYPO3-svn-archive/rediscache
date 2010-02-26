<?php

/***************************************************************
* Copyright notice
*
* (c) 2010 by Christopher Hlubek - networkteam GmbH
*
* All rights reserved
*
* This script is part of the rediscache project. The rediscache project
* is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This is a file of the rediscache project.
 * http://forge.typo3.org/projects/show/extension-rediscache
 *
 * Project sponsored by:
 * networkteam GmbH - http://www.networkteam.com/
 *
 * $Id: $
 */

/**
 * A Redis cache backend implementation.
 *
 * The backend depends on the phpredis (http://github.com/owlient/phpredis) php extension for
 * the Redis client.
 *
 * @author Christopher Hlubek <hlubek@networkteam.com>
 *
 * @package TYPO3
 * @subpackage rediscache
 */
class tx_rediscache_cache_backend_RedisBackend extends t3lib_cache_backend_AbstractBackend {

	/**
	 * A prefix to seperate stored data from other data possible stored in the APC
	 *
	 * @var string
	 */
	protected $identifierPrefix;

	/**
	 * The hostname / IP of the Redis server.
	 * Defaults to 127.0.0.1.
	 *
	 * @var string
	 */
	protected $hostname = '127.0.0.1';

	/**
	 * The port of the Redis server.
	 * Defaults to 6379.
	 *
	 * @var int
	 */
	protected $port = 6379;

	/**
	 * A shared static Redis client
	 */
	protected static $redis = NULL;

	/**
	 * Constructs this backend
	 *
	 * @param mixed $options Configuration options - unused here
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($options = array()) {
		if (!extension_loaded('redis')) {
			throw new t3lib_cache_Exception(
				'The PHP extension "redis" must be installed and loaded in order to use the Redis backend.',
				1267180079
			);
		}

		if (self::$redis === NULL) {
			self::$redis = new Redis();
			self::$redis->connect($this->hostname, $this->port);
		}
		$this->redis = self::$redis;

		parent::__construct($options);
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if no cache frontend has been set.
	 * @throws InvalidArgumentException if the identifier is not valid
	 * @throws t3lib_cache_exception_InvalidData if $data is not a string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1232986818
			);
		}

		if (!is_string($data)) {
			throw new t3lib_cache_exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1232986825
			);
		}

		$tags[] = '%REDISBE%';
		$expiration = $lifetime !== NULL ? $lifetime : $this->defaultLifetime;

		$entryKey = $this->getEntryKey($entryIdentifier);
		$entryTagsKey = $this->getEntryTagsKey($entryIdentifier);

		$this->redis->set($entryKey, $data);

		// Update tags for entry
		$addTags = $tags;
		$removeTags = array();
		$existingTags = $this->redis->sMembers($entryTagsKey);
		if (!empty($existingTags)) {
			$addTags = array_diff($tags, $existingTags);
			$removeTags = array_diff($existingTags, $tags);
		}
		$this->addTagsToIdentifier($addTags, $entryIdentifier);
		$this->removeTagsFromIdentifier($removeTags, $entryIdentifier);
		if ($expiration > 0) {
			$this->redis->setTimeout($entryKey, $expiration);
			$this->redis->setTimeout($entryTagsKey, $expiration);
		}		
	}

	/**
	 *
	 */
	protected function getEntryKey($entryIdentifier) {
		return $this->identifierPrefix . $entryIdentifier;
	}

	/**
	 *
	 */
	protected function getEntryTagsKey($entryIdentifier) {
		return $this->getEntryKey($entryIdentifier) . '--tags';
	}

	/**
	 *
	 */
	protected function getTagKey($tag) {
		return $this->identifierPrefix . 'tag--' . $tag;
	}

	/**
	 *
	 */
	protected function addTagsToIdentifier($tags, $entryIdentifier) {
		$entryTagsKey = $this->getEntryTagsKey($entryIdentifier);
		foreach ($tags as $tag) {
			$this->redis->sAdd($entryTagsKey, $tag);
			$this->redis->sAdd($this->getTagKey($tag), $entryIdentifier);
		}
	}

	/**
	 *
	 */
	protected function removeTagsFromIdentifier($tags, $entryIdentifier) {
		foreach ($tags as $tag) {
			$this->redis->sRemove($entryTagsKey, $tag);
			$tagKey = $this->getTagKey($tag);
			$this->redis->sRemove($tagKey, $entryIdentifier);
			// TODO Cleanup orphaned tag
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function get($entryIdentifier) {
		return $this->redis->get($this->getEntryKey($entryIdentifier));
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function has($entryIdentifier) {
		return $this->redis->exists($this->getEntryKey($entryIdentifier));
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function remove($entryIdentifier) {
		if ($this->has($entryIdentifier)) {
			$entryKey = $this->getEntryKey($entryIdentifier);
			$entryTagsKey = $this->getEntryTagsKey($entryIdentifier);
			$tags = $this->redis->sMembers($entryTagsKey);

			foreach ($tags as $tag) {
				$tagKey = $this->getTagKey($tag);
				$this->redis->sRemove($tagKey, $entryIdentifier);
				// TODO Cleanup orphaned tag
			}

			// Delete entry and entry tags from redis
			$this->redis->delete($entryKey, $entryTagsKey);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function findIdentifiersByTag($tag) {
		return $this->redis->sMembers($this->getTagKey($tag));
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tags.
	 *
	 * @param array Array of tags to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function findIdentifiersByTags(array $tags) {
		$tagKeys = array();
		foreach ($tags as $tag) {
			$tagKeys[] = $this->getTagKey($tag);
		}
		return $this->redis->sInter($tagKeys);
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param string $identifier Identifier to find tags by
	 * @return array Array with tags
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function findTagsByIdentifier($entryIdentifier) {
		$this->redis->sMember($this->getEntryTagsKey($entryIdentifier));
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function flush() {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1232986971
			);
		}
		$this->flushByTag('%REDISBE%');
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified
	 * tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);

		foreach ($identifiers as $identifier) {
			$this->remove($identifier);
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param array	The tags the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTags(array $tags) {
		foreach ($tags as $tag) {
			$this->flushByTag($tag);
		}
	}

	/**
	 * TODO delete empty tags
	 *
	 * @return void
	 */
	public function collectGarbage() {

	}

	/**
	 * @param string $identifierPrefix
	 * @return void
	 */
	public function setIdentifierPrefix($identifierPrefix) {
		$this->identifierPrefix = $identifierPrefix;
	}

	/**
	 * @param string $hostname
	 * @return void
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * @param string $port
	 * @return void
	 */
	public function setPort($port) {
		$this->port = $port;
	}

}

?>