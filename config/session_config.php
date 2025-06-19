<?php
/**
 * Session Configuration Bridge File
 * This file maintains backward compatibility with existing code
 * while moving the actual session configuration logic to system/security/session_config.php
 * 
 * @package    Mamatid Health Center System
 * @subpackage Config
 * @version    1.0
 */

// Include the actual session config file from the new location
require_once __DIR__ . '/../system/security/session_config.php';
?> 