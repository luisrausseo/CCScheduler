# Coaching Session Scheduler (QTKM)
This web application finds the first match in which the person who needs coaching has a shift which overlaps with a QTKM: Quality agent.

The application retrieves workers' schedule from a Google Calendar which feeds from When2Work. After finding the best match, then the app pulls data from an Office 365 room calendar to see if the resource is free to schedule the coaching session.

## Google Calendar API

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

![picture alt](/img/GoogleCalendarSharing.PNG "Google Calendar Sharing Setting")