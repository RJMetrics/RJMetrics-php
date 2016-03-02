<?php

namespace RJMetrics;

use Httpful;

class UnableToConnectException extends \Exception {}
class InvalidRequestException extends \Exception {}

class Client {

	const API_BASE = "https://connect.rjmetrics.com/v2";
	const SANDBOX_BASE = "https://sandbox-connect.rjmetrics.com/v2";

	private $clientId, $apiKey, $timeoutInSeconds;

	/**
	 * Client::__construct
	 *
	 * Takes a `clientId` and `apiKey`. If either are invalid, this function will
	 * immediately throw an `InvalidArgumentException`. It will then hit the live API to test the
	 * given credentials. If that authentication fails, it will throw a `RJMetrics\UnableToConnectException`.
	 *
	 * Returns itself to enable chaining.
	 *
	 * @param int $clientId
	 * @param string $apiKey
	 * @param :optional int $timeoutInSeconds
	 * @return object
	 */
	public function __construct($clientId, $apiKey, $timeoutInSeconds = 10) {
		if(!is_int($clientId) || $clientId <= 0)
			throw new \InvalidArgumentException(
				"Invalid client ID: {$clientId} -- must be a positive integer.");

		if(!is_int($timeoutInSeconds) || $timeoutInSeconds <= 0)
			throw new \InvalidArgumentException(
				"Invalid timeout: {$timeoutInSeconds} seconds -- must be a positive integer.");

		if(!is_string($apiKey))
			throw new \InvalidArgumentException(
				"Invalid API key: {$apiKey} -- must be a string.");

		$this->clientId = $clientId;
		$this->apiKey = $apiKey;
		$this->timeoutInSeconds = $timeoutInSeconds;

		if(!$this->authenticate())
			throw new UnableToConnectException("Connection failed. Please double check your credentials.");

		return $this;
	}

	/**
	 * Client::authenticate
	 *
	 * This function will run authentication against the live API. Will return true if authentication
	 * succeeds, false if it fails.
	 *
	 * @return boolean
	 */
	public function authenticate() {
		$testData = json_decode("[{\"keys\":[\"id\"],\"id\":1}]");

		try {
			$this->pushData("test", $testData, self::SANDBOX_BASE);
		} catch(InvalidRequestException $e) {
			return false;
		}

		return true;
	}

	/**
	 * Client::pushData
	 *
	 * Given a table name and a valid php object or array, this function will push it to the Import
	 * API. If `tableName` or `data` are invalid, this function will throw an `InvalidArgumentException`.
	 *
	 * Per the Import API spec, it breaks `data` down into chunks of 100 records per request.
	 *
	 * Returns an array of Httpful response objects.
	 *
	 * @param string $table
	 * @param array/object $data
	 * @param :optional string $url
	 * @return array
	 */
	public function pushData($tableName, $data, $url = self::API_BASE) {
		if(!is_object($data) && !is_array($data)) {
			throw new \InvalidArgumentException(
				"Invalid data -- must be a valid PHP array or object.");
		}

		if(!is_string($tableName)) {
			throw new \InvalidArgumentException(
				"Invalid table name: '{$tableName}' -- must be a string.");
		}

		if(!is_array($data)) {
			$data = array($data);
		}

		$_this = $this;

		$responses = array_map(function($subArray) use ($_this, $tableName, $url) {
			return $_this->makePushDataAPICall($tableName, $subArray, $url);
		}, array_chunk($data, 100));

		return $responses;
	}

	/**
	 * Client::makePushDataAPICall
	 *
	 * Internal function to wrap the Import API using Httpful.
	 *
	 * @param string $tableName
	 * @param array $data
	 * @param :optional string $url
	 * @return object
	 */
	public function makePushDataAPICall($tableName, array $data, $url = self::API_BASE) {
		$requestUrl = "{$url}/client/{$this->clientId}/table/{$tableName}/data?apikey={$this->apiKey}";

		$response = \Httpful\Request::post($requestUrl)
			->mime("application/json")
			->body($data)
			->timeout($this->timeoutInSeconds)
			->send();

		if($response->hasErrors())
			throw new InvalidRequestException(
				"The Import API returned: {$response->code} {$response->body->message}. ".
				"Reasons: ".implode($response->body->reasons, ","));

		return $response;
	}


}

?>
