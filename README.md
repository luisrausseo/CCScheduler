# Coaching Session Scheduler (QTKM)
This web application finds the first match in which the person who needs coaching has a shift which overlaps with a QTKM: Quality agent.

The application retrieves workers' schedule from a Google Calendar which feeds from When2Work. After finding the best match, then the app pulls data from an Office 365 room calendar to see if the resource is free to schedule the coaching session.

## Google Calendar API

This application uses the [Google API Client Library for PHP](https://developers.google.com/api-client-library/php/) (google-api-php-client-2.2.2). 