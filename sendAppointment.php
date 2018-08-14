<?php
	function sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location) {
    $domain = 'ttu.edu';

    //Create Email Headers
    $mime_boundary = "----Meeting Booking----".MD5(TIME());

    $headers = "From: ".$from_name." <".$from_address.">\n";
    $headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
    $headers .= "Content-class: urn:content-classes:calendarmessage\n";
    
    //Create Email Body (HTML)
    $message = "--$mime_boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= "<html>\n";
    $message .= "<body>\n";
    $message .= '<p>Dear '.$to_name.',</p>';
    $message .= '<p>'.$description.'</p>';
    $message .= "</body>\n";
    $message .= "</html>\n";
    $message .= "--$mime_boundary\r\n";

    $ical = 'BEGIN:VCALENDAR' . "\r\n" .
    'PRODID:-//Microsoft Corporation//Outlook 16.0 MIMEDIR//EN' . "\r\n" .
    'VERSION:2.0' . "\r\n" .
    'METHOD:REQUEST' . "\r\n" .
	'X-CALSTART:' .date("Ymd\THis", strtotime($startTime)). "Z\r\n" .
	'X-CALEND:' .date("Ymd\THis", strtotime($endTime)). "Z\r\n" .
	'X-WR-RELCALID:{00000185-EB2C-52FA-5EA7-50CBD5B4273E}' . "\r\n" . 
	'X-WR-CALNAME:IT Help Central Service Desk Coaching Room 101C' . "\r\n" .
	'X-PRIMARY-CALENDAR:TRUE' . "\r\n" .
	'X-OWNER;CN="IT Help Central Service Desk Coaching Room 101C":mailto:resourceascrm101c@ttu.edu' . "\r\n" .
	'X-MS-OLK-WKHRSTART;TZID="Central Standard Time":073000' . "\r\n" .
	'X-MS-OLK-WKHREND;TZID="Central Standard Time":160000' . "\r\n" .
	'X-MS-OLK-WKHRDAYS:MO,TU,WE,TH,FR' . "\r\n" .
    'BEGIN:VTIMEZONE' . "\r\n" .
    'TZID:Central Standard Time' . "\r\n" .
    'BEGIN:STANDARD' . "\r\n" .
    'DTSTART:16011104T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11' . "\r\n" .
    'TZOFFSETFROM:-0500' . "\r\n" .
    'TZOFFSETTO:-0600' . "\r\n" .
    'TZNAME:EST' . "\r\n" .
    'END:STANDARD' . "\r\n" .
    'BEGIN:DAYLIGHT' . "\r\n" .
    'DTSTART:16010311T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3' . "\r\n" .
    'TZOFFSETFROM:-0500' . "\r\n" .
    'TZOFFSETTO:-0600' . "\r\n" .
    'TZNAME:EDST' . "\r\n" .
    'END:DAYLIGHT' . "\r\n" .
    'END:VTIMEZONE' . "\r\n" .	
    'BEGIN:VEVENT' . "\r\n" .
	'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED:MAILTO:'.$to_address. "\r\n" .
	'CLASS:PUBLIC' . "\r\n" .
	'CREATED:' .date("Ymd\THis"). "Z\r\n" .
	'DESCRIPTION:'. $description . "\r\n" .
    'ORGANIZER;CN="'.$from_name.'":MAILTO:'.$from_address. "\r\n" .
    'LAST-MODIFIED:' . date("Ymd\TGis") . "\r\n" .
    'UID:'. md5(uniqid(mt_rand(), true)) ."@".$domain."\r\n" .
    'DTSTAMP:'.date("Ymd\TGis"). "\r\n" .
    'DTSTART;TZID="Central Standard Time":'.date("Ymd\THis", strtotime($startTime)). "\r\n" .
    'DTEND;TZID="Central Standard Time":'.date("Ymd\THis", strtotime($endTime)). "\r\n" .
    'TRANSP:OPAQUE'. "\r\n" .
    'SEQUENCE:0'. "\r\n" .
    'SUMMARY;LANGUAGE=en-us:' . $subject . "\r\n" .
    'LOCATION:' . $location . "\r\n" .
    'CLASS:PUBLIC'. "\r\n" .
    'PRIORITY:5'. "\r\n" .
    'BEGIN:VALARM' . "\r\n" .
    'TRIGGER:-PT15M' . "\r\n" .
    'ACTION:DISPLAY' . "\r\n" .
    'DESCRIPTION:Reminder' . "\r\n" .
    'END:VALARM' . "\r\n" .
    'END:VEVENT'. "\r\n" .
    'END:VCALENDAR'. "\r\n";
	
	//echo $ical;
    $message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\n";
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= $ical;

    $mailsent = mail($to_address, $subject, $message, $headers);
	//$mailsent = mail($from_address, $subject, $message, $headers);

    return ($mailsent)?(true):(false);
	}
	
	//getUserEmail: runs a PowerShell command to query the eRaider's username email address from AD.
	function getUserEmail($input){
		$user =  explode('|', $var)[1];
		$psPath = "powershell.exe";
		$psScript = "(Get-ADUser ". $user ." -Properties mail).mail";
		$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
		exec($runCMD,$out,$ret);
		return $out[0];
	}

	if ($_POST['agentSelector']){
		$from_name = "IT Help Central Service Desk Coaching Room 101C";        
		$from_address = "resourceascrm101c@ttu.edu";        
		$to_name = "Luis Rausseo";        
		$to_address = getUserEmail($_POST['agentSelector']);;        
		$startTime = "07/27/2018 18:00:00";        
		$endTime = "07/27/2018 19:00:00";        
		$subject = "Coaching Session with " . $to_name . " and _COACHED_";        
		$description = "Please come to ASC Room 101C for a coaching session for Quality Evaluation #NUMBER. If you are on a call during the time of coaching session, please come after your call ends.";        
		$location = "IT Help Central Service Desk Coaching Room 101C";
		sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location);
	}



?>