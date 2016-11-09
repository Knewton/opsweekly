<?php

if (!file_exists('/app/configuration/secureconfig.php')) {
    die('Cannot find secureconfig.php! It must be in /app/configuration and named secureconfig.php');
}

require_once('/app/configuration/secureconfig.php');

// Login details for the MySQL database, where all the data is stored.
// The empty database schema is stored in opsweekly.sql
$mysql_host = $mysql_credentials['hostname'];
$mysql_user = $mysql_credentials['username'];
$mysql_pass = $mysql_credentials['password'];

$pagerduty_apikey = $pagerduty_credentials['apikey'];

// The domain name your company uses to send email from, used for a reply-to address
// for weekly reports
$email_from_domain = "knewton.com";

/**
 * Authentication configuration
 * Nagdash must know who is requesting pages, as every update entry etc is unique
 * to a single person.
 * Therefore, you must define a function somewhere called getUsername()
 * that will return a plain text username string to Nagdash, e.g. "ldenness" or "bsmith"
 **/
function getUsername() {
    // Use the PHP_AUTH_USER header which contains the username when Basic auth is used.
    return $_SERVER['PHP_AUTH_USER'];
}

/**
 * Team configuration
 * Arrays of teams, the key being the Virtual Host FQDN, e.g. opsweekly.mycompany.com
 *
 * Options:
 * display_name: Used for display purposes, your nice team name.
 * email_report_to: The email address the weekly reports users write should be emailed to
 * database: The name of the MySQL database the data for this team is stored in
 * event_versioning: Set to 'on' to store each event with a unique id each time the on-call report is saved.
 *                   Set to 'off' to only update existing events and insert new ones each time the on-call report is saved. Makes for a cleaner database.
 *                   If undefined, defaults to 'on', for backwards compatibility.
 * oncall: false or an array. If false, hides the oncall sections of the interface. If true, please complete the other information.
 *   - provider: The plugin you wish to use to retrieve on call information for the user to complete
 *   - provider_options: An array of options that you wish to pass to the provider for this team's on call searching
 *       - There are variables for the options that are subsituted within the provider. See their docs for more info
 *   - timezone: The PHP timezone string that your on-call rotation starts in
 *   - start: Inputted into strtotime, this is when your oncall rotation starts.
 *            e.g. Match this to Pagerduty if you use that for scheduling.
 *   - end: Inputted into strtotime, this is when your oncall rotation ends.
 *          e.g. Match this to Pagerduty if you use that for scheduling.
 **/
$teams = array(
    "boot2docker:41811" => array(
        "display_name" => "Knewton Test ",
        "email_report_to" => "mendy.berkowitz@knewton.com",
        "database" => "opsweekly",
        "event_versioning" => "off",
        "weekly_hints" => array(),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                "pagerduty_escalation_policy_id" => "PO9PDSW"
            ),
            "timezone" => "America/New_York",
            "start" => "tuesday 12:00",
            "end" => "tuesday 12:00",
        ),
    ),
    "learn-oncall.knewton.net" => array(
        "display_name" => "Learn Team",
        "email_report_to" => "learn-team-eng@knewton.com",
        "database" => "opsweeklylearn",
        "event_versioning" => "off",
        "weekly_hints" => array(),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                "pagerduty_escalation_policy_id" => "PO9PDSW"
            ),
            "timezone" => "America/New_York",
            "start" => "tuesday 12:00",
            "end" => "tuesday 12:00",
        ),
    ),
    "teach-oncall.knewton.net" => array(
        "display_name" => "Teach Team",
        "email_report_to" => "teach-team-eng@knewton.com",
        "database" => "opsweeklyteach",
        "event_versioning" => "off",
        "weekly_hints" => array(),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                "pagerduty_escalation_policy_id" => "PVCHSK9"
            ),
            "timezone" => "America/New_York",
            "start" => "tuesday 12:00",
            "end" => "tuesday 12:00",
        ),
    ),
    "content-oncall.knewton.net" => array(
        "display_name" => "Content Team",
        "email_report_to" => "content-team-eng@knewton.com",
        "database" => "opsweeklycontent",
        "event_versioning" => "off",
        "weekly_hints" => array(),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                "pagerduty_escalation_policy_id" => "PFJ5HOQ"
            ),
            "timezone" => "America/New_York",
            "start" => "tuesday 12:00",
            "end" => "tuesday 12:00",
        ),
    ),
    "systems-oncall.knewton.net" => array(
        "display_name" => "Systems Team",
        "email_report_to" => "systems@knewton.com",
        "database" => "opsweeklysystems",
        "event_versioning" => "off",
        "weekly_hints" => array(),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                "pagerduty_escalation_policy_id" => "PJSPASA"
            ),
            "timezone" => "America/New_York",
            "start" => "friday 10:00",
            "end" => "friday 10:00",
        ),
    )
);

/**
 * Weekly hint providers
 *  A 'weekly' provider, or 'hints' is designed to prompt the
 *  user to remember what they did in the last week, so they can
 *  fill out their weekly report more accurately.
 *
 *  It appears on the right hand side of the "add" screen.
 *  Select which providers you want for your team using the 'weekly_hints'
 *  key in the teams array.
 *
 **/
$weekly_providers = array();


/**
 * Oncall providers
 * These are used to retrieve information given a time period about the alerts the requesting
 * user received.
 **/
$oncall_providers = array(
    "pagerduty" => array(
        "display_name" => "Pagerduty",
        "lib" => "providers/oncall/pagerduty.php",
        "options" => array(
            "base_url" => "https://knewton.pagerduty.com/api/v1",
            "apikey" => $pagerduty_apikey
        ),
    ),
);

// The number of search results per page
$search_results_per_page = 25;

// Path to disk where a debug error log file can be written
$error_log_file = "/tmp/opsweekly_debug.log";

// Dev FQDN
// An alternative FQDN that will be accepted by Opsweekly for running a development copy elsewhere
// Fed into preg_replace so regexes are allowed
$dev_fqdn = "/(\w+)boot2docker/";
// The prod FQDN is then subsituted in place of the above string.
$prod_fqdn = "mycompany.com";

// Global configuration for irccat, used to send messages to IRC about weekly meetings.
$irccat_hostname = '';
$irccat_port = 12345;
