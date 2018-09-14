<?php
	//getUserEmail: runs a PowerShell command to query the eRaider's username email address from AD.
	function getUserEmail($input){
		$psPath = "powershell.exe";
		$psScript = "(Get-ADUser ". $input ." -Properties mail).mail";
		$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
		exec($runCMD, $out, $ret);
		return $out[0];
	}
	
	function getCoacherEmail($input){
		$psPath = "powershell.exe";
		$psScript = "C:\\xampp\\htdocs\\apps\\CSV\\getQuality.ps1";
		$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
		exec($runCMD, $out, $ret);
		
		//Remove headers
		array_shift($out);
		array_shift($out);
		array_shift($out);
		
		foreach($out as $line){
			$arr = preg_split ("/\s+/", $line);
			if (sizeof($arr) > 1){
				if ($input == "Nathaniel Ness"){
					$input = "Nate Ness";
				}
				if ($input == "Blakley McCurdy"){
					$input = "Blake McCurdy";
				}
				if (strpos($arr[1] . " " . $arr[2], $input) !== false) {
					return $arr[3];
				}
			}
		}
	}
	
	function findRoomTime($input) {
		//Change timezone
		ini_set("date.timezone", "America/Chicago");
		
		//Input from html file
		$start_agent = $input[0];
		$end_agent = $input[1];
		$start_coacher = $input[2];
		$end_coacher = $input[3];
		
		//Get calendar from Outlook
		$cal_file = file("calendar.txt");
		$calendar = file($cal_file[0])
					or die("Calendar unavailable!");
		
		//Calculate time range for coaching session_cache_expire
		$date_min = max($start_agent, $start_coacher);
		$date_max = min($end_agent, $end_coacher);
		
		//Add 30 minutes to date_min to account for late shifts
		//$date_min = date('c', strtotime($date_min . '+ 30 minute'));
		
		//Get current date of appointment
		$date_coach = $start_agent;
		
		//Set appointment to date_min
		$coach_begin = $date_min;
		
		//Create empty array for room's busy times at date_coach
		$room_busy = []; 
		
		//Find if room is busy at date_coach
		foreach ($calendar as $line_num => $line) {
			if (strpos($line, "SUMMARY") !== false) {
				$line_date = date("m-d-Y", strtotime(substr($calendar[$line_num + 1], strpos($calendar[$line_num + 1], ":") + 1)));
				if (date("m-d-Y", strtotime($date_coach)) == $line_date) {
					$room_busy[] = date("c", strtotime(substr($calendar[$line_num + 1], strpos($calendar[$line_num + 1], ":") + 1)));
					$room_busy[] = date("c", strtotime(substr($calendar[$line_num + 2], strpos($calendar[$line_num + 2], ":") + 1)));
				} 
			}
		}
		
		//Adds weekly staff meeting on Thursdays
		if (date("D", strtotime($date_coach)) == "Thu") {
			$room_busy[] =	date("c", strtotime(date("Y-m-d", strtotime($date_coach)) . "T14:00:00-05:00"));	
			$room_busy[] =	date("c", strtotime(date("Y-m-d", strtotime($date_coach)) . "T15:00:00-05:00"));	
		}
		
		if (empty($room_busy)) {
			$coach_end = date('c', strtotime($coach_begin . '+ 15 minute'));
		} else {
			usort($room_busy, "date_sort");
			while (!empty($room_busy)) {
				$coach_end = date('c', strtotime($coach_begin . '+ 15 minute'));
				$busy_start = array_shift($room_busy);
				$busy_end = array_shift($room_busy);
				if (max($busy_start, $coach_begin) < min($busy_end, $coach_end)) {
					$coach_begin = date('c', strtotime($busy_end));
					$coach_end = date('c', strtotime($coach_begin . '+ 15 minute'));
				} else {
					//break;
				}
			}
		}	
		return array($coach_begin, $coach_end);
	}
	
	//Auxiliary function to sort dates
	function date_sort($a, $b) {
		return strtotime($a) - strtotime($b);
	}
	
	//Build ical event and sends email from within server
	function sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location) {
		$domain = 'ttu.edu';
		$room_name = "IT Help Central Service Desk Coaching Room 101C";        
		$room_address = "resourceascrm101c@ttu.edu"; 
		$gmail_sender = "ithc.qtkm.qualitydev@gmail.com";
		$gmail_name = "ITHC Quality Session Scheduler";
		

		//Create Email Headers
		$mime_boundary = "----Meeting Booking----".MD5(TIME());

		//$headers = "From: ".$room_name." <".$room_address.">\n";
		//$headers .= "Reply-To: ".$room_name." <".$room_address.">\n";
		$headers = "From: ".$gmail_name." <".$gmail_sender.">\n";
		$headers .= "Reply-To: ".$gmail_name." <".$gmail_sender.">\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
		$headers .= "Content-class: urn:content-classes:calendarmessage\n";
		//$headers .= "X-MS-Exchange-Organization-CalendarBooking-Response: Accept";
		
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
		'ATTENDEE;CN="'.$from_name.'";ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED:MAILTO:'.$from_address. "\r\n" .
		'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED:MAILTO:'.$to_address. "\r\n" .
		'CLASS:PUBLIC' . "\r\n" .
		'CREATED:' .date("Ymd\THis"). "Z\r\n" .
		'DESCRIPTION:'. $description . "\r\n" .
		'ORGANIZER;CN="IT Help Central Quality":mailto:quality.ithelpcentral@ttu.edu'. "\r\n" .
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
		
		$message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\n";
		$message .= "Content-Transfer-Encoding: 8bit\n\n";
		$message .= $ical;

		//$mailsent = mail($to_address, $subject, $message, $headers);
		//$mailsent = mail($from_address, $subject, $message, $headers);
		//$mailsent = mail($room_address, $subject, $message, $headers);

		return ($mailsent)?(true):(false);
	}
	
	if ($_POST['agentSelector']){
		$agent = explode('|', $_POST['agentSelector']);
		$agent_name = explode(',', $agent[0])[0] . " " . explode(',', $agent[0])[1];
		$agent_address = getUserEmail($agent[1]);
		$coacher_name = substr($_POST['coacherAgent'], 0, (strpos($_POST['coacherAgent'], "-") - 3));
		$coacher_address = getCoacherEmail($coacher_name);
		$coach_time = findRoomTime($_POST['dates']);
		$startTime = $coach_time[0];        
		$endTime = $coach_time[1];    
		$subject = "Coaching Session with " . $coacher_name . " and " . $agent_name;
		$description = "Please come to ASC Room 101C for a coaching session for a Quality Evaluation. If you are on a call during the time of coaching session, please come after your call ends.";        
		$location = "IT Help Central Service Desk Coaching Room 101C";
		//Debug
		echo $agent_address . " | " . $agent_name . " <br> " . $coacher_address . " | " . $coacher_name . "<br>";
		echo $subject . "<br>";
		echo $description . "<br>";
		echo $location . "<br>";
		echo $startTime . "<br>";
		echo $endTime . "<br>";
		//sendIcalEvent($coacher_name, $coacher_address, $agent_name, $agent_address, $startTime, $endTime, $subject, $description, $location);
	}
?>