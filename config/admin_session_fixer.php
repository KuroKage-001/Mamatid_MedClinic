<?php
/**
 * Session Fix Bridge File
 * This file maintains backward compatibility with existing code
 * while consolidating session fix logic into admin_session_config.php
 * 
 * @package    Mamatid Health Center System
 * @subpackage Config
 * @version    1.1
 */

// Include the consolidated session configuration file which now includes session fixing
require_once __DIR__ . '/../system/security/admin_session_config.php';
?> 