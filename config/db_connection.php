<?php
/**
 * Database Connection Bridge File
 * This file maintains backward compatibility with existing code
 * while moving the actual connection logic to system/database/db_connection.php
 * 
 * @package    Mamatid Health Center System
 * @subpackage Config
 * @version    1.0
 */

// Include the actual connection file from the new location
require_once __DIR__ . '/../system/database/db_connection.php';
?> 