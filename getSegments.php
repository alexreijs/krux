<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "/credentials.php");

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://dataconsole.kruxdigital.com/api/adm/v2/audience_segment?api_key=HdXsiH4e");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$output = curl_exec($ch);

curl_close($ch);


$fn = "/tmp/segments.csv";
$fp = fopen($fn, "w");


fwrite($fp, implode(',', array(
	'id',
	'name',
	'category',
	'sub_category',
	'description',
	'is_active',
	'last_compute_time',
	'uuid',
	'uuid_long',
	'page_views',
	'population'
)) . "\n");

if ($output) {
	$segments = json_decode($output, TRUE);
	$count = 0;
	foreach ($segments  as $segmentIndex => $segment) {

		$lastComputeTime = new DateTime($segment['last_compute_time']);
		$lastComputeTime->setTimezone(new DateTimeZone('Europe/Amsterdam'));
		$lastComputeTimeString = $lastComputeTime->format('Y-m-d H:i:s');

		//print_r($segment);

		$columns = array(
			$segment['id'],
			'"' . str_replace('"', '""', preg_replace("/[\n\r]/", " ", $segment['name'])) . '"',
			'"' . $segment['category'] . '"',
			'"' . $segment['sub_category'] . '"',
			'"' . str_replace('"', '""', preg_replace("/[\n\r]/", " ", $segment['description'])) . '"',
			$segment['is_active'],
			$lastComputeTimeString,
			$segment['segment_uuid'],
			$segment['segment_uuid_long'],
			$segment['page_views'],
			$segment['population']
		);

		foreach ($columns as $i => $column) {
			$line = $column . ($i == count($columns) - 1 ? "\n" : ",");
			fwrite($fp, $line);
		}
		$count++;
	}

	print("Saved " . $count . " segments to file ...\n");
}

fclose($fp);


printf("Uploading to Google Storage\n");
$shell = shell_exec("/usr/local/bin/gsutil cp /tmp/segments.csv gs://api-hub-output/krux/segments/");

printf("Deleting downloaded files\n");
shell_exec("rm /tmp/segments.csv");

?>
