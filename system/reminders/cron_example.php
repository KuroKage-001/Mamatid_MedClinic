<?php
// Example Cron Job Configuration for Appointment Reminders
//
// This file demonstrates how to set up a cron job to run the appointment reminder script.
// The reminder script checks for appointments occurring within 30 minutes and sends email notifications.
//
// To set up a cron job on a Linux/Unix server:
// 1. Access your server's crontab with: crontab -e
// 2. Add a line like the following to run the script every 5 minutes:
//    */5 * * * * php /full/path/to/system/reminders/send_appointment_reminders.php
//
// To set up a scheduled task on Windows:
// 1. Open Task Scheduler
// 2. Create a new task
// 3. Set the trigger to run every 5 minutes
// 4. Add an action to start a program: php.exe
// 5. Set the argument as: C:\path\to\system\reminders\send_appointment_reminders.php
//
// Notes:
// - Running every 5 minutes ensures timely reminders while minimizing server load
// - Make sure the PHP interpreter is in your system path or specify the full path to php
// - Ensure the script has appropriate permissions to run
// - Consider logging output to troubleshoot any issues

// This is just an example file - no actual code to execute
echo "This is a demonstration file for setting up a cron job.\n";
echo "The actual reminder script is send_appointment_reminders.php\n";
?> 