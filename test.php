<?php
	//getUserEmail: runs a PowerShell command to query the eRaider's username email address from AD.
	function getUserEmail($input){
		//$user =  explode('|', $input)[1];
		$psPath = "powershell.exe";
		$psScript = "(Get-ADUser ". $input ." -Properties mail).mail";
		$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
		exec($runCMD,$out,$ret);
		echo $out[0] . "<br>";
	}
	
	function getCoacherEmail($input){
		$agents = array("Camden Loper","Alina Petprachan","Luis Rausseo","Kelia Smoot","Nathaniel Ness","Blakley McCurdy","Kira Randolph");
		$username = array("cloper","apetprac","lrausseo","ksmoot","nness","bmccurdy","kirandol");
		$name = substr($input, 0, (strpos($input, "-") - 3));
		$key = array_search($name, $agents);
		echo getUserEmail($username[$key]);
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
		$date_min = date('c', strtotime($date_min . '+ 30 minute'));
		
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
			echo "Room is free!<br >\n";
			echo "Coaching session begins on ". $coach_begin . "<br >\n";
			echo "Coaching session end on ". $coach_end . "<br >\n";
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
			echo "Room was busy!<br >\n";
			echo "Coaching session begins on ". $coach_begin . "<br >\n";
			echo "Coaching session end on ". $coach_end . "<br >\n";
		}	
	}
	
	//Auxiliary function to sort dates
	function date_sort($a, $b) {
		return strtotime($a) - strtotime($b);
	}
	
	if ($_POST['agentSelector']){
		getUserEmail(explode('|', $_POST['agentSelector'])[1]);
		getCoacherEmail($_POST['coacherAgent']);
		//echo "<input id='appt-time' type='time' name='appt-time' step='900'>";
		//echo "<button onclick='sendEmail()'>Schedule Coaching Session</button>";
		findRoomTime($_POST['dates']);
	}
?>