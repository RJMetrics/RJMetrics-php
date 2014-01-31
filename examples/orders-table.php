<?php

require 'vendor/autoload.php';
$client = new RJMetrics\Client(0, "your-api-key");

function syncOrder($client, $order) {
	$dataToPush = new stdClass();
	$dataToPush->id = $order->id;
	$dataToPush->user_id = $order->user_id;
	$dataToPush->value = $order->value;
	$dataToPush->sku = $order->sku;
	$dataToPush->keys = array("id");

	return $client->pushData("orders", $dataToPush);
}

function fakeOrderGenerator($id, $userId, $value, $sku) {
	$toReturn = new stdClass();

	$toReturn->id = $id;
	$toReturn->user_id = $userId;
	$toReturn->value = $value;
	$toReturn->sku = $sku;

	return $toReturn;
}

$orders = array(
	fakeOrderGenerator(1, 1, 58.40, "milky-white-suede-shoes"),
	fakeOrderGenerator(2, 1, 23.99, "red-buttons-down-fleece"),
	fakeOrderGenerator(3, 2, 5.00, "bottle-o-bubbles"),
	fakeOrderGenerator(4, 3, 120.01, "zebra-striped-game-boy"),
	fakeOrderGenerator(5, 5, 9.90, "kitten-mittons")
);

if($client->authenticate()) {
	foreach($orders as $order) {
		$responses = syncOrder($client, $order);

		foreach($responses as $response) {
			if($response->code == 201)
				print("Synced order with id {$order->id}\n");
			else
				print("Failed to sync order with id {$order->id}\n");
		}
	}
}

?>
