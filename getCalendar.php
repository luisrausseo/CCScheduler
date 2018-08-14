<?php
	//NameFixer: Fixes the name for people that have a different name in AD and W2W. Separate name in First_Name and Last_Name.
	//Accepts an string of the form 'First_Name,Last_Name' and returns an array when pos 0 is First_Name and pos 1 is Last_Name.
	function NameFixer($var) {
		if ($var == "James,Fields") {
			$var = "Austin,Fields";
		}
		if ($var == "Kenji,Flores") {
			$var = "Kenji,Nishizaki";
		}
		if ($var == "Maria,Ramey") {
			$var = "Victoria,Rivas";
		}
		if ($var == "Nate,Ness") {
			$var = "Nathaniel,Ness";
		}
		$var = explode('|', $var)[0];
		$commaPos = strpos($var, ",");
		$FirstName = substr($var, 0, $commaPos);
		$LastName = substr($var, $commaPos+1, strlen($var));
		return array($FirstName, $LastName);
	}
	
	function processDrpdown($selectedVal) {
		require_once 'google-api-php-client-2.2.2/vendor/autoload.php';

		putenv('GOOGLE_APPLICATION_CREDENTIALS=CalendarRetrieverWeb-e568bafabecc.json');
		$client = new Google_Client();
		$client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
		$client->useApplicationDefaultCredentials();
		$service = new Google_Service_Calendar($client);

		$calendarId = 'q7an5o5e6nef2udot5clf6i8ck@group.calendar.google.com';
		$optParams = array(
		  'maxResults' => 1000,
		  'orderBy' => 'startTime',
		  'singleEvents' => true,
		  'timeMin' => date('c', strtotime(date('c') . '+ 1 day')),
		);
		$results = $service->events->listEvents($calendarId, $optParams);

		//search parameters
		$input = NameFixer($selectedVal);
		$SLname = $input[1];
		$SFname = $input[0];
		
		
		if (empty($results->getItems())) {
			print "No upcoming events found.\n";
		} else {
			$agentFound = false;
			foreach ($results->getItems() as $event) {
				$sim = similar_text($SFname . " " . $SLname, substr($event->getSummary(), 0, (strpos($event->getSummary(), "-")-4)), $perc);
				// if (($perc >= 75)) {
					// $name =$event->getSummary();
					// $start = $event->getStart()->dateTime;
					// $end = $event->getEnd()->dateTime;
					// continue;
				// }
				// if (!empty($start)) {
					// if((max($start, $event->getStart()->dateTime) < min($end, $event->getEnd()->dateTime)) And (strpos($event->getSummary(), "Quality") !== false)){
						// echo "<h2>Match found!</h2>";
						// echo "<a>" . $name . "</a><br><br>";
						// echo "<h3>Assign evaluation to:</h3>";
						// echo "<a>" . $event->getSummary() . "</a><br>";
						// echo "<a>" . substr($start, 0, 10) . "</a>";
						// $agentFound = true;
						// break;
					// }
				// }
				if (($perc >= 75)) {
					//echo "<a>" . $event->getSummary() . "</a><br>";
					$name =$event->getSummary();
					$start = $event->getStart()->dateTime;
					$end = $event->getEnd()->dateTime;
					foreach ($results->getItems() as $event) {
						if (!empty($start)) {
							if((max($start, $event->getStart()->dateTime) < min($end, $event->getEnd()->dateTime)) And (strpos($event->getSummary(), "Quality") !== false)){
								echo "<h2>Match found!</h2>";
								echo "<a>" . $name . "</a><br>";
								echo "<a id='start_agent'>" . $start . "</a><br>";
								echo "<a id='end_agent'>" . $end . "</a><br><br>";
								echo "<h3>Assign evaluation to:</h3>";
								echo "<a id='coacherAgent'>" . $event->getSummary() . "</a><br>";
								echo "<a id='coachDate'>" . substr($start, 0, 10) . "</a><br>";
								echo "<a id='start_coacher'>" . $event->getStart()->dateTime . "</a><br>";
								echo "<a id='end_coacher'>" . $event->getEnd()->dateTime . "</a><br>";
								$agentFound = true;
								break;
							}
						}
					}
					if ($agentFound == true) {
						break;
					}
				}
			}
			if ($agentFound == false) {
				echo "<h3>No shifts overlaping with quality found!</h3>";
			}
		}
	}
	
	if ($_POST['agentSelector']){
		ini_set("date.timezone", "America/Chicago");
		processDrpdown($_POST['agentSelector']);
	}
?>