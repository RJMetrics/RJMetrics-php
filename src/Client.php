<?php

namespace RJMetrics;

use Httpful;

class UnableToConnectException extends \Exception {}
class InvalidRequestException extends \Exception {}

class Client {

	const API_BASE = "https://connect.rjmetrics.com/v2";
	const SANDBOX_BASE = "https://sandbox-connect.rjmetrics.com/v2";

	private $clientId, $apiKey;

	public function __construct($clientId, $apiKey) {
		if(!is_int($clientId) || $clientId <= 0)
			throw new \InvalidArgumentException(
				"Invalid client ID: {$clientId} -- must be a positive integer.");

		if(!is_string($apiKey))
			throw new \InvalidArgumentException(
				"Invalid API key: {$apiKey} -- must be a string.");

		$this->clientId = $clientId;
		$this->apiKey = $apiKey;

		if(!$this->test())
			throw new UnableToConnectException("Connection failed. Please double check your credentials.");
	}

	public function test() {
		$testData = json_decode("[{\"keys\":[\"id\"],\"id\":1}]");

		return $this->isSuccess($this->pushData("test", $testData, self::SANDBOX_BASE));
	}

	public function pushData($table, $data, $url = self::API_BASE) {
		if(!is_object($data) && !is_array($data))
			throw new \InvalidArgumentException(
				"Invalid data -- must be a valid PHP array or object.");

		if(!is_string($table))
			throw new \InvalidArgumentException(
				"Invalid table name: '{$table}' -- must be a string.");

		if(!is_array($data))
			$data = [$data];

		$responses = array_map(function($subArray) use ($table, $url) {
			return $this->makePushDataAPICall($table, $subArray, $url);
		}, array_chunk($data, 100));

		return $responses;
	}

	private function isSuccess(array $responses) {
		return count(array_filter($responses, function($response) {
			return $response->code >= 400;
		})) == 0;
	}

	private function makePushDataAPICall($table, array $data, $url = self::API_BASE) {
		$requestUrl = "{$url}/client/{$this->clientId}/table/$table/data?apikey={$this->apiKey}";

		$response = \Httpful\Request::post($requestUrl)
			->mime("application/json")
			->body($data)
			->send();

		if($response->hasErrors())
			throw new InvalidRequestException(
				"The Import API returned: {$response->code} {$response->body->message}. ".
				"Reasons: ".implode($response->body->reasons, ","));

		return $response;
	}


}

?>
