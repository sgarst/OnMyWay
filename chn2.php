<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<title>Leaving Now...</title>
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0.min.css" />
		<meta name="description" content="Coming Home Now" />
		<meta name="author" content="Sam Garst" />
	</head>
	<body>
		<article>
			<h2>Leaving Now... </h2>
		</article>
		<?php

		// Based on example from  http://www.raywenderlich.com/2941/how-to-write-a-simple-phpmysql-web-service-for-an-ios-app
		function getStatusCodeMessage($status) {
			$codes = Array(100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => '(Unused)', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported');
			return (isset($codes[$status])) ? $codes[$status] : '';
		}

		// Helper method to send a HTTP response code/message
		function sendResponse($status = 200, $body = '', $content_type = 'text/html') {
			$status_header = 'HTTP/1.1 ' . $status . ' ' . getStatusCodeMessage($status);
			header($status_header);
			header('Content-type: ' . $content_type);
		}

		function sendEmail($to, $subject, $message) {
			$headers = 'From:sgarst@gmail.com' . "\r\n" . 'Reply-To:sgarst@gmail.com';
			if (mail($to, $subject, $message, $headers)) {
				echo("<article><br><h3>Message Sent!</h3>");
				echo("<h4>To: $to <br>Subject: $subject <br>Body: $message</h4></article>");
			} else {
				echo("<article><h4>Message failed...</h4>");
				echo("<br><h4>To: $to <br>Subject: $subject <br>Body: $message </h4></article>");
			};
		}

		function duration($origin, $destination, $mode) {
			$dur = "...soonish?";
			$sturl = 'http://maps.googleapis.com/maps/api/directions/json?origin=' . $origin . '&destination=' . $destination . '&mode=' . $mode . '&units=imperial&sensor=false';
			$ch = curl_init($sturl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$res = curl_exec($ch);
			$resinfo = curl_getinfo($ch);
			curl_close($ch);
			if ($resinfo['http_code'] === 200) {
				$results = json_decode($res, TRUE);
				$dur = $results[routes][0][legs][0][duration][text];
			} 
			if ($dur == "") {
				$dur = "...soonish?";
			}
			return array($resinfo['http_code'], $dur);
		}//end duration()

		function geocode($address) {
			$key = "AIzaSyAaOgg1ItXlA9sr9mCcybvm4SsJy-jhFNY";
			$address = urlencode($address);
			$sturl = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';
			$ch = curl_init($sturl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$res = curl_exec($ch);
			$resinfo = curl_getinfo($ch);
			curl_close($ch);
			if ($resinfo['http_code'] === 200) {
				$results = json_decode($res, TRUE);
				$latitude = $results[results][0][geometry][location][lat];
				$longitude = $results[results][0][geometry][location][lng];
			} else {
				$latitude = "0";
				$longitude = "0";
			}
			return array($resinfo['http_code'], $latitude, $longitude);
		}//end geocode()

		class SendCHN {

			function sendchn() {
				// Check for required parameters
				if (isset($_POST["sender"]) && isset($_POST["home"]) && isset($_POST["curr_lat"]) && isset($_POST["curr_lng"]) 
					&& (!empty($_POST["sender"])) && (!empty($_POST["home"])) && (!empty($_POST["curr_lat"])) && (!empty($_POST["curr_lng"])) 
					&& (((!empty($_POST["phone"])) && (!empty($_POST["carrier"]))) || (!empty($_POST["email"])))) {
					// Put parameters into local variables
					//echo("<article><h4>sendwhat: $sendwhat</h4></article>");
					$message = ($_POST["message"]);
					$sender = ($_POST["sender"]);
					$sendwhat = $_POST["sendwhat"];
					$location = $_POST["curr_lat"] . "," . $_POST["curr_lng"];
					$home = $_POST["home"];
					$mode = $_POST["mode"];
					$phone = preg_replace('/[^0-9]/', '', $_POST["phone"]);
					$carrier = $_POST["carrier"];
					list($status, $home_lat, $home_lng) = geocode($home);
					$home_latlng = $home_lat . "," . $home_lng;
					list($status, $duration) = duration($location, $home_latlng, $mode);
					if ($sendwhat == "sms") {
						$recipient = $phone . "@" . $carrier;
						$subject = "";
						$message = $sender . " should get home in about " . $duration . ".\n".$message."\n";
					} else {
						$recipient = filter_var(($_POST["email"]), FILTER_SANITIZE_EMAIL);
						$subject = "Leaving now....[" . $sender . "]";
						$message = "Should get home in about " . $duration . ".\n".$message."\n";
					}
					//Now.... send email or SMS
					sendEmail($recipient, $subject, $message, $location);
					$result = array($recipient, $message);
					sendResponse(200, json_encode($result));
					return true;
				}
				echo("<article><h4>Problem...</h4>");
				echo("<br><h4>Perhaps you didn't enter in all the fields?</h4></article>");
				sendResponse(400, 'Invalid request');
				return false;
			}

		}

		// This is the first thing that gets called when this page is loaded
		// Creates a new instance of the SendCHN class and calls the sendchn method
		$api = new SendCHN;
		?>
	</body>
</html>