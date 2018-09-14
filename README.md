# Coaching Session Scheduler (QTKM)
This web application finds the first match in which the person who needs coaching has a shift which overlaps with a QTKM: Quality agent.

The application retrieves workers' schedule from a Google Calendar which feeds from When2Work. After finding the best match, then the app pulls data from an Office 365 room calendar to see if the resource is free to schedule the coaching session.

##Development

### Google Calendar API

This application uses the [Google API Client Library for PHP](https://developers.google.com/api-client-library/php/) (google-api-php-client-2.2.2). 

```PHP
require_once 'google-api-php-client-2.2.2/vendor/autoload.php';

putenv('GOOGLE_APPLICATION_CREDENTIALS=yourCalendarServiceAccount.json');
$client = new Google_Client();
$client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
$client->useApplicationDefaultCredentials();
$service = new Google_Service_Calendar($client);

$calendarId = 'yourGoogleCalendarID@group.calendar.google.com';
$optParams = array(
	'maxResults' => 1000,
	'orderBy' => 'startTime',
	'singleEvents' => true,
	'timeMin' => date('c', strtotime(date('c') . 'next day at midnight')),
	);
$results = $service->events->listEvents($calendarId, $optParams);

if (empty($results->getItems())) {
	print "No upcoming events found.\n";
} else {
	foreach ($results->getItems() as $event) {
		//Do something with the $event->getSummary() which shows the title of the calendar event.
	}
}
```

For this to work, a Service Account must be created at [Google Cloud Console](https://console.cloud.google.com) and add the JSON file in the same path of the php file. 

Additionally the calendar must be shared to the Google Service Account. 

![picture alt](/img/GoogleCalendarSharing.PNG "Google Calendar Sharing Settings")

### Office 365 Room Calendar

The room calendar is retrieved by opening a share-link provided and parsing trough the .ics file. 

```PHP
//Get calendar from Outlook
$cal_file = file("calendar.txt"); //This file contains the link of the .ics file.
$calendar = file($cal_file[0])
			or die("Calendar unavailable!");
			
//Create empty array for room's busy times
		$room_busy = []; 
		
//Find if room is busy
foreach ($calendar as $line_num => $line) {
	if (strpos($line, "SUMMARY") !== false) {
		$line_date = date("m-d-Y", strtotime(substr($calendar[$line_num + 1], strpos($calendar[$line_num + 1], ":") + 1)));
		if (date("m-d-Y", strtotime($date_coach)) == $line_date) {
			$room_busy[] = date("c", strtotime(substr($calendar[$line_num + 1], strpos($calendar[$line_num + 1], ":") + 1)));
			$room_busy[] = date("c", strtotime(substr($calendar[$line_num + 2], strpos($calendar[$line_num + 2], ":") + 1)));
		} 
	}
}
```

### Matching Algorithm

To match and worker to a Quality agent, or to find a time in which the room is available for both agents, the following criteria was used to define overlapping time frames:

```PHP
if (max($start_1, $start_2) < min($end_1, $end_2)) {
	//Time frames overlap
} else {
	//Do not overlap
}
```

![picture alt](/img/MatchAlgorithm.PNG "Time Match Algorithm")

### Microsoft PowerShell

To keep the application updated without human intervention, worker's information is retrieved from Active Directory by running a PowerShell script.

To retrieve an individual's email, the following function is used:

```PHP
function getUserEmail($input){
	$psPath = "powershell.exe";
	$psScript = "(Get-ADUser ". $input ." -Properties mail).mail";
	$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
	exec($runCMD, $out, $ret);
	return $out[0];
}
```

To get information of certain groups, the $psScript variable is changed to run the following command:

```PowerShell
Import-Module ActiveDirectory
Get-ADGroupMember AD_GROUP | Select Name, @{Name="FirstName";Expression={(Get-ADUser $_.distinguishedName -Properties EmailAddress).GivenName}},@{Name="Last Name";Expression={(Get-ADUser $_.distinguishedName -Properties EmailAddress).Surname}}, @{Name="Email";Expression={(Get-ADUser $_.distinguishedName -Properties mail).mail}} | Sort Email
```
