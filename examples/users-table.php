<?php

require 'vendor/autoload.php';
$client = new RJMetrics\Client(0, "your-api-key");

function syncUser($client, $user) {
	$dataToPush = new stdClass();
	$dataToPush->id = $user->id;
	$dataToPush->email = $user->email;
	$dataToPush->acquisition_source = $user->acquisitionSource;
	// user_id is the unique key here, since each user should only
	// have one record in this table
	$dataToPush->keys = array("id");

	// table named "users"
	return $client->pushData("users", $dataToPush);
}

// let's define some fake users
function fakeUserGenerator($id, $email, $acquisitionSource) {
	$toReturn = new stdClass();

	$toReturn->id = $id;
	$toReturn->email = $email;
	$toReturn->acquisitionSource = $acquisitionSource;

	return $toReturn;
}

$users = array(
	fakeUserGenerator(1, "joe@schmo.com", "PPC"),
	fakeUserGenerator(2, "mike@smith.com", "PPC"),
	fakeUserGenerator(3, "lorem@ipsum.com", "Referral"),
	fakeUserGenerator(4, "george@vandelay.com", "Organic"),
	fakeUserGenerator(5, "larry@google.com", "Organic"),
);

// make sure the client is authenticated before we do anything
if($client->authenticate()) {
	// iterate through users and push data
	foreach($users as $user) {
		$responses = syncUser($client, $user);

		// api calls always return an array of responses
		foreach($responses as $response) {
			if($response->code == 201)
				print("Synced user with id {$user->id}\n");
			else
				print("Failed to sync user with id {$user->id}\n");
		}
	}
}

?>
