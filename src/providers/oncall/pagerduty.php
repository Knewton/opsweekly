<?php

/**
 * Pagerduty on call provider
 */

/** Plugin specific variables required
 * Global Config:
 *  - base_url: The path to your Pagerduty API, e.g. https://company.pagerduty.com/api/v1
 *  - username: A user that can access your Pagerduty account using the API
 *  - password: The password for the user above
 *
 * Team Config:
 *  - pagerduty_service_id: The service ID that this team uses for alerts to be collected
 *
 */


/**
 * getOnCallNotifications - Returns the notifications for a given time period and parameters
 *
 * Parameters:
 *   $on_call_name - The username of the user compiling this report
 *   $provider_global_config - All options from config.php in $oncall_providers - That is, global options.
 *   $provider_team_config - All options from config.php in $teams - That is, specific team configuration options
 *   $start - The unix timestamp of when to start looking for notifications
 *   $end - The unix timestamp of when to stop looking for notifications
 *
 * Returns 0 or more notifications as array()
 * - Each notification should have the following keys:
 *    - time: Unix timestamp of when the alert was sent to the user
 *    - hostname: Ideally contains the hostname of the problem. Must be populated but feel free to make bogus if not applicable.
 *    - service: Contains the service name or a description of the problem. Must be populated. Perhaps use "Host Check" for host alerts.
 *    - output: The plugin output, e.g. from Nagios, describing the issue so the user can reference easily/remember issue
 *    - state: The level of the problem. One of: CRITICAL, WARNING, UNKNOWN, DOWN
 *    - notes: Concatenation of the notes attached to the pd incident
 */

function getOnCallNotifications($name, $global_config, $team_config, $start, $end) {
    $base_url = $global_config['base_url'];
    $username = $global_config['username'];
    $password = $global_config['password'];
    $apikey = $global_config['apikey'];
    $service_id = $team_config['pagerduty_service_id'];
    $escalation_policy_id = $team_config['pagerduty_escalation_policy_id'];

    if ($base_url !== '' && $username !== '' && $password !== '' && $service_id !== '') {
      // convert single PagerDuty service, to array construct in order to hold multiple services.
      if (!is_array($service_id)) {
        if (strlen($service_id) > 0) {
          $service_id = array($service_id);
        } else {
          $service_id = array();
        }
      }

      if (!is_array($escalation_policy_id)) {
        if (strlen($escalation_policy_id) > 0) {
          $escalation_policy_id = array($escalation_policy_id);
        } else {
          $escalation_policy_id = array();
        }
      }

      // loop through the escalation policies and get all of the services
      foreach ($escalation_policy_id as $eid) {
        $escalation_policy_json = doPagerdutyAPICall("/escalation_policies/$eid", array(),
                $base_url, $username, $password, $apikey);
        if (!$escalation_policy = json_decode($escalation_policy_json)) {
            return 'Could not retrieve escalation policy from Pagerduty! Please check your login detail';
        }

        $services = $escalation_policy->escalation_policy->services;
        foreach ($services as $service) {
            array_push($service_id, $service->id);
        }
      }

      // deduplicate and sanitize ids
      $valid_sids = array();
      foreach ($service_id as $sid) {
        // check if the service id is formated correctly
        if (!sanitizePagerDutyServiceId($sid)) {
            logline('Incorect format for PagerDuty Service ID: ' . $sid);
            // skip to the next Service ID in the array
            continue;
        }

        // using an associcative array as a simulated set to deduplicate
        $valid_sids[$sid] = 1;
      }

      $service_ids_to_query = array_keys($valid_sids);

      // loop through PagerDuty's maximum incidents count per API request.
      $running_total = 0;
      do {
          // Connect to the Pagerduty API and collect all incidents in the time period.
          $parameters = array(
          'since' => date('c', $start),
          'service' => $service_ids_to_query,
          'until' => date('c', $end),
          'offset' => $running_total,
          );

          $incident_json = doPagerdutyAPICall('/incidents', $parameters, $base_url, $username, $password, $apikey);
          if (!$incidents = json_decode($incident_json)) {
              return 'Could not retrieve incidents from Pagerduty! Please check your login details';
          }
          // skip if no incidents are recorded
          if (count($incidents->incidents) == 0) {
              continue;
          }

          logline("Incidents on Service IDs: " . implode(", ", $service_ids_to_query));
          logline("Total incidents: " . $incidents->total);
          logline("Limit in this request: " . $incidents->limit);
          logline("Offset: " . $incidents->offset);

          $running_total += count($incidents->incidents);

          logline("Running total: " . $running_total);
          foreach ($incidents->incidents as $incident) {
              $time = strtotime($incident->created_on);
              $state = $incident->urgency;
              $service = $incident->service->name;

              $output = $incident->trigger_details_html_url;
              $output .= "\n";

              // Add to the output all the trigger_summary_data info
              foreach ($incident->trigger_summary_data as $key => $key_data) {
                  if (is_string($key_data)) {
                      $output .= "$key: $key_data\n";
                  }
              }

              $output .= $incident->url;

              // try to determine the hostname
              if (isset($incident->trigger_summary_data->HOSTNAME)) {
                  $hostname = $incident->trigger_summary_data->HOSTNAME;
              } else {
                  // fallback is to just say it was pagerduty that sent it in
                  $hostname = "Pagerduty";
              }

              // Retrieve notes for the incident
              $notes_json = doPagerdutyAPICall("/incidents/{$incident->id}/notes", array(),
                    $base_url, $username, $password, $apikey);
              $notes_content = "";
              if ($notes_json === false || !$notes = json_decode($notes_json)) {
                  logline("Could not retrieve notes from Pagerduty!");
              } else {
                  $num_notes = count($notes->notes);
                  logline("Found {$num_notes} notes on incident {$incident->id}.");
                  foreach ($notes->notes as $note) {
                      $notes_content .= "[{$note->created_at}] {$note->content}   ";
                  }
              }

              logline("Adding data for incident {$incident->id} to notifications");
              $notifications[] = array("time" => $time, "hostname" => $hostname, "service" => $service, "output" => $output, "state" => "$state", "notes" => $notes_content);
          }
      } while ($running_total < $incidents->total);

      // if no incidents are reported, don't generate the table
      if (count($notifications) == 0 ) {
        return array();
      } else {
        return $notifications;
      }
    } else {
        return false;
    }
}

function doPagerdutyAPICall($path, $parameters, $pagerduty_baseurl, $pagerduty_username, $pagerduty_password, $pagerduty_apikey) {

    if (isset($pagerduty_apikey)) {
        $context = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Token token=$pagerduty_apikey"
            )
        ));
    } else {

        $context = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Basic " . base64_encode("$pagerduty_username:$pagerduty_password")
            )
        ));
    }

    $params = null;
    foreach ($parameters as $key => $value) {
        if (isset($params)) {
            $params .= '&';
        } else {
            $params = '?';
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $params .= sprintf('%s=%s', $key, $value);
    }
    $contents = file_get_contents($pagerduty_baseurl . $path . $params, false, $context);
    if ($contents === false) {
        $headers = var_export($http_response_header, true);
        logline("PD API request failed. \n{$headers}");
    }

    return $contents;
}

function whoIsOnCall($schedule_id, $time = null) {

    $until = $since = date('c', isset($time) ? $time : time());
    $parameters = array(
        'since' => $since,
        'until' => $until,
        'overflow' => 'true',
    );

    $json = doPagerdutyAPICall("/schedules/{$schedule_id}/entries", $parameters);

    if (false === ($scheddata = json_decode($json))) {
        return false;
    }

    if ($scheddata->total == 0) {
        return false;
    }

    if ($scheddata->entries['0']->user->name == "") {
        return false;
    }

    $oncalldetails = array();
    $oncalldetails['person'] = $scheddata->entries['0']->user->name;
    $oncalldetails['email'] = $scheddata->entries['0']->user->email;
    $oncalldetails['start'] = strtotime($scheddata->entries['0']->start);
    $oncalldetails['end'] = strtotime($scheddata->entries['0']->end);

    return $oncalldetails;

}

function sanitizePagerDutyServiceId($service_id) {
    $pattern = '/^[A-Z0-9]{7}$/';
    if (preg_match($pattern, $service_id)) {
        return true;
    } else {
        return false;
    }
}
?>
