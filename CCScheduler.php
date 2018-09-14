<!DOCTYPE html>
<?php 
	class AgentInfo {
		public $username;
		public $firstName;
		public $lastName;
		
		function __construct($username, $firstName, $lastName) {
			$this->username = $username;
			$this->firstName = $firstName;
			$this->lastName = $lastName;
		}
	}
	
	function getADGroupMember($input) {
		$psPath = "powershell.exe";
		$psScript = "C:\\xampp\\htdocs\\apps\\CSV\\get" . $input . ".ps1";
		$runCMD = $psPath." -executionPolicy Unrestricted ".$psScript." 2>&1"; 
		exec($runCMD, $out, $ret);
		
		//Delete headers
		if ($input == "SA") {
			for ($x = 0; $x < 4; $x++) {
				array_shift($out);
			}
		} elseif ($input == "Techs") {
			for ($x = 0; $x < 6; $x++) {
				array_shift($out);
			} 
		} elseif ($input == "Sups") {
			for ($x = 0; $x < 5; $x++) {
				array_shift($out);
			}
		}
		
		//Clean array
		foreach($out as $key => $value) {
			$user = preg_split("/\s+/", $value)[0];
			if (empty($value)){
				array_splice($out, $key, 1);
			}
		}
		
		//Delete last value (not sure why is emtpty)
		array_pop($out); 
		return($out);
	}
	
	function getAgentsInfo($input) {
		$agents = [];
		$ignore = str_getcsv(file("usersIgnored.txt")[0]);
		foreach ($input as $item) {
			$agent = new AgentInfo("","","");
			$arr = preg_split ("/\s+/", $item);
			if (in_array($arr[0], $ignore, true)) {
				continue;
			}
			$agent->username = $arr[0];
			$agent->firstName = $arr[1];
			if (sizeof($arr) == 4) {
				$agent->lastName = $arr[2];
			} elseif (sizeof($arr) == 5) {
				$agent->lastName = $arr[3];
			}
			$agents[] = $agent;
		}
		return $agents;
	}
?>
<html class="ttu no-js" lang="en-us">
	<head>
		<title>Coaching Session Scheduler</title>
		<link rel="stylesheet" href="/_ttu-template/2017/css/style.min.css?20170830" media="all" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	</head>

	<body>
		<h1>Coaching Session Scheduler</h1>
			
				<legend>Person being coached:</legend>
				<select id="agentSelector">
						<option value="" selected disabled hidden>Choose an agent</option>
						<option value="" disabled><strong>Student Analysts</strong></option>
						<?php
							$SAs = getAgentsInfo(getADGroupMember("SA"));
							foreach ($SAs as $agent){
								echo "<option value=". $agent->firstName . "," . $agent->lastName . "|" . $agent->username . ">&nbsp&nbsp&nbsp-", $agent->firstName . " " . $agent->lastName, "</option>";
							}
						?>
						<option value="" disabled>Technicians</option>
						<?php
							$Techs = getAgentsInfo(getADGroupMember("Techs"));
							foreach ($Techs as $agent){
								echo "<option value=". $agent->firstName . "," . $agent->lastName . "|" . $agent->username . ">&nbsp&nbsp&nbsp-", $agent->firstName . " " . $agent->lastName, "</option>";
							}
						?>
						<option value="" disabled>Supervisors</option>
						<?php
							$Sups = getAgentsInfo(getADGroupMember("Sups"));
							foreach ($Sups as $agent){
								echo "<option value=". $agent->firstName . "," . $agent->lastName . "|" . $agent->username . ">&nbsp&nbsp&nbsp-", $agent->firstName . " " . $agent->lastName, "</option>";
							}
						?>
					</select>
		
			<div id="output">
			</div>
			
			<div id="OutlookBttn">
				<button id='nextBttn' onclick="reRoll()">Next agent's shift</button>
				<button id='nextQual' onclick="nextQual()">Next quality shift</button><br><br>
				<button id='apptBttn' onclick="setupApptnmt()">Schedule coaching session</button><br>
			</div>
		
		<script>						
			$(document).ready(function(){
				$('#OutlookBttn').hide();
				$('#agentSelector').change(function(){
					var inputValue = $(this).val();
					$.post('getCalendar.php', { agentSelector: inputValue, stopValue: -1 }, function(data){
						$('#output').empty();
						$('#output').append(data);
						if(!$('#OutlookBttn').is(':visible')) {
							$('#OutlookBttn').toggle();
						}
						if(!$('#nextQual').is(':visible')) {
							$('#nextQual').toggle();
						}
						$('#nextBttn').text("Next agent's shift");
						if(!$('#apptBttn').is(':visible')) {
							$(apptBttn).toggle();
						}
					});
				});
			});
			
			function setupApptnmt() {
				var user = $('#agentSelector').val();
				var coacher = $('#coacherAgent').text();
				var start_agent = $('#start_agent').text();
				var end_agent = $('#end_agent').text();
				var start_coacher = $('#start_coacher').text();
				var end_coacher = $('#end_coacher').text();
				$.post('sendAppointment.php', { agentSelector: user, coacherAgent: coacher, dates: [start_agent, end_agent, start_coacher, end_coacher] }, function(data){
						$('#OutlookBttn').append(data);
						$('#apptBttn').hide();
				});
			};
			
			function reRoll() {
				var inputValue = $('#agentSelector').val();
				var stopPoint = $('#stopPoint').text();
				$.post('getCalendar.php', { agentSelector: inputValue, stopValue: stopPoint}, function(data){
						$('#output').empty();
						$('#output').append(data);
						if($('#nextBttn').text() == "Next agent's shift") {
							$('#nextBttn').text("Undo");
							$('#nextQual').hide();
						} else {	
							$('#nextBttn').text("Next agent's shift");
							if(!$('#nextQual').is(':visible')) {
								$('#nextQual').toggle();
							}
						}
						
					});
			};
			
			function nextQual() {
				var inputValue = $('#agentSelector').val();
				if ($('#nextQual').text() == "Next quality shift"){
					var stopPoint = -2;
				} else {
					var stopPoint = -1;
				}
				$.post('getCalendar.php', { agentSelector: inputValue, stopValue: stopPoint}, function(data){
						$('#output').empty();
						$('#output').append(data);
						if (stopPoint == -2) {
							$('#nextQual').text("Undo");
							$('#nextBttn').hide();
						} else {
							$('#nextQual').text("Next quality shift");
							$('#nextBttn').toggle();
						}
					});
			};
			
		</script>
	</body>
</html>