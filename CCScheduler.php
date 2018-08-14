<!DOCTYPE html>
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
							$file = fopen("CSV/sa.csv", "r");
							//Removes header and unwanted data
							$agents_SA = fgetcsv($file, 1000, ",");
							$agents_SA = fgetcsv($file, 1000, ",");
							$agents_SA = fgetcsv($file, 1000, ",");
							while (! feof($file)) {
								$agents_SA = fgetcsv($file, 1000, ",");
								if (! empty($agents_SA[0])){
									if ($FnameSpace = strpos($agents_SA[1], " ")){
										$agents_SA[1] = substr($agents_SA[1], 0, $FnameSpace);
									}
									echo "<option value=". $agents_SA[1] . "," . $agents_SA[2] . "|" . $agents_SA[0] . ">&nbsp&nbsp&nbsp-", $agents_SA[1] . " " . $agents_SA[2], "</option>";
								}
							}
							fclose($file);
						?>
						<option value="" disabled>Technicians</option>
						<?php
							$file = fopen("CSV/techs.csv", "r");
							//Removes header and unwanted data
							$agents_techs = fgetcsv($file, 1000, ",");
							$agents_techs = fgetcsv($file, 1000, ",");
							$agents_techs = fgetcsv($file, 1000, ",");
							$agents_techs = fgetcsv($file, 1000, ",");
							$agents_techs = fgetcsv($file, 1000, ",");
							while (! feof($file)) {
								$agents_techs = fgetcsv($file, 1000, ",");
								if ((! empty($agents_techs[0])) or (! empty($agents_techs[1]))){
									if ($FnameSpace = strpos($agents_techs[1], " ")){
										$agents_techs[1] = substr($agents_techs[1], 0, $FnameSpace);
									}
									echo "<option value=". $agents_techs[1] . "," . $agents_techs[2] . "|" . $agents_techs[0] . ">&nbsp&nbsp&nbsp-", $agents_techs[1] . " " . $agents_techs[2], "</option>";
								}
							}
							fclose($file);
						?>
						<option value="" disabled>Supervisors</option>
						<?php
							$file = fopen("CSV/sups.csv", "r");
							//Removes header and unwanted data
							$agents_sups = fgetcsv($file, 1000, ",");
							$agents_sups = fgetcsv($file, 1000, ",");
							$agents_sups = fgetcsv($file, 1000, ",");
							$agents_sups = fgetcsv($file, 1000, ",");
							while (! feof($file)) {
								$agents_sups = fgetcsv($file, 1000, ",");
								if (! empty($agents_sups[0])){
									if ($FnameSpace = strpos($agents_sups[1], " ")){
										$agents_sups[1] = substr($agents_sups[1], 0, $FnameSpace);
									}
									echo "<option value=". $agents_sups[1] . "," . $agents_sups[2] . "|" . $agents_sups[0] . ">&nbsp&nbsp&nbsp-", $agents_sups[1] . " " . $agents_sups[2], "</option>";
								}
							}
							fclose($file);
						?>
					</select>
		
			<div id="output">
			</div>
			
			<div id="OutlookBttn">
				<button onclick="setupApptnmt()">Schedule Coaching Session</button><br>
			</div>
		
		<script>						
			$(document).ready(function(){
				$('#OutlookBttn').hide();
				$('#agentSelector').change(function(){
					var inputValue = $(this).val();
					$.post('getCalendar.php', { agentSelector: inputValue }, function(data){
						$('#output').empty();
						$('#output').append(data);
						if(!$('#OutlookBttn').is(':visible')) {
							$('#OutlookBttn').toggle();
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
				$.post('test.php', { agentSelector: user, coacherAgent: coacher, dates: [start_agent, end_agent, start_coacher, end_coacher] }, function(data){
						$('#OutlookBttn').append(data);
				});
			};
		</script>
	</body>
</html>