<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "/credentials.php");

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://dataconsole.kruxdigital.com/api/adm/v2/event?api_key=HdXsiH4e");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$output = curl_exec($ch);

curl_close($ch);


$fn = "/tmp/events.csv";
$fp = fopen($fn, "w");



fwrite($fp, implode(',', array(
	'created_at',
	'event_beacon',
	'event_type_code',
	'event_type_name',
	'event_uid',
	'is_active',
	'name',
	'resource_uri'
)) . "\n");

if ($output) {
	$events = json_decode($output, TRUE);

	$count = 0;
	foreach ($events  as $eventIndex => $event) {

		$createdAtTime = new DateTime($event['created_at']);
		$createdAtTime->setTimezone(new DateTimeZone('Europe/Amsterdam'));
		$createdAtTimeString = $createdAtTime->format('Y-m-d H:i:s');

		//print_r($event);

		$columns = array(
			$createdAtTimeString,
			$event['event_beacon'],
			$event['event_type']['code'],
			$event['event_type']['name'],
			$event['event_uid'],
			$event['is_active'],
			$event['name'],
			$event['resource_uri']
		);

		foreach ($columns as $i => $column) {
			$line = $column . ($i == count($columns) - 1 ? "\n" : ",");
			fwrite($fp, $line);
		}
		$count++;
	}

	print("Saved " . $count . " events to file ...\n");
}

fclose($fp);


printf("Uploading to Google Storage\n");
$shell = shell_exec("/usr/local/bin/gsutil cp /tmp/events.csv gs://api-hub-output/krux/events/");

printf("Deleting downloaded files\n");
shell_exec("rm /tmp/events.csv");

?>
