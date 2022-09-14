<?php

namespace Seam;

use Seam\Workspace;
use Seam\Device;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\RequestException as RequestException;
use GuzzleHttp\Exception\ClientException as ClientException;
use GuzzleHttp\Exception\ClientErrorResponseException as ClientErrorResponseException;
use \Exception as Exception;

final class SeamClient
{
  public DevicesClient $devices;
  public WorkspacesClient $workspaces;

  public function __construct(
    $api_key,
    $endpoint = "https://connect.getseam.com",
    $throw_http_errors = false
  ) {
    $this->api_key = $api_key;
    $this->client = new HTTPClient([
      "base_uri" => $endpoint,
      "timeout" => 20.0,
      "headers" => [
        "Authorization" => "Bearer " . $this->api_key,
        "User-Agent" => "Seam PHP Client 0.0.1",
      ],
      "http_errors" => $throw_http_errors,
    ]);
    $this->devices = new DevicesClient($this);
    $this->action_attempts = new ActionAttemptsClient($this);
    $this->workspaces = new WorkspacesClient($this);
    $this->access_codes = new AccessCodesClient($this);
    $this->events = new EventsClient($this);
    $this->connect_webviews = new ConnectWebviewsClient($this);
    $this->connected_accounts = new ConnectedAccountsClient($this);
    $this->locks = new LocksClient($this);
  }

  public function request(
    $method,
    $path,
    $json = null,
    $query = null,
    $inner_object = null
  ) {
    $options = [];
    if ($json) {
      $options["json"] = $json;
    }
    if ($query) {
      $options["query"] = $query;
    }

    // TODO handle request errors
    $response = $this->client->request($method, $path, $options);
    $statusCode = $response->getStatusCode();
    var_dump($response);

    $res_json = null;
    try {
      $res_json = json_decode($response->getBody());
    } catch (Exception $ignoreError) {
    }

    if (($res_json->error ?? null) != null) {
      throw new Exception(
        "Error Calling \"" .
          $method .
          " " .
          $path .
          "\" : " .
          ($res_json->error->type ?? "") .
          ": " .
          $res_json->error->message
      );
    }

    if ($statusCode >= 400) {
      throw new Exception(
        "HTTP Error: [" . $statusCode . "] " . $method . " " . $path
      );
    }

    if ($inner_object) {
      if (($res_json->$inner_object ?? null) == null) {
        throw new Exception(
          'Missing Inner Object "' .
            $inner_object .
            '" for ' .
            $method .
            " " .
            $path
        );
      }
      return $res_json->$inner_object;
    }
    return $res_json;
  }
}

final class DevicesClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * Get Device
   */
  public function get(string $device_id): Device|null
  {
    $device = Device::from_json(
      $this->seam->request(
        "GET",
        "devices/get",
        query: ["device_id" => $device_id],
        inner_object: "device"
      )
    );
    return $device;
  }

  /**
   * List devices
   * @return Device[]
   */
  public function list(): array
  {
    return array_map(
      fn($d) => Device::from_json($d),
      $this->seam->request("GET", "devices/list", inner_object: "devices")
    );
  }
}

final class WorkspacesClient
{
  public function __construct($seam)
  {
    $this->seam = $seam;
  }

  public function get(): Workspace
  {
    return Workspace::from_json(
      $this->seam->request("GET", "workspaces/get", inner_object: "workspace")
    );
  }

  public function list()
  {
    return Workspace::from_json(
      $this->seam->request("GET", "workspaces/list", inner_object: "workspaces")
    );
  }

  public function reset_sandbox()
  {
    $res = $this->seam->client->request("POST", "workspaces/reset_sandbox");
    return json_decode($res->getBody());
  }

  public function _internal_load_august_factory()
  {
    $res = $this->seam->client->request(
      "POST",
      "internal/scenarios/factories/load",
      [
        "json" => [
          "factory_name" => "create_august_devices",
          "input" => ["num" => 1],
          "sync" => true,
        ],
      ]
    );

    // sleep for 0.2 seconds
    usleep(200000);
  }
}

final class ActionAttemptsClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * List Action Attempts
   * @return ActionAttempt[]
   */
  public function list(): array
  {
    return array_map(
      fn($a) => ActionAttempt::from_json($a),
      $this->seam->request(
        "GET",
        "action_attempts/list",
        inner_object: "action_attempts"
      )
    );
  }

  public function get(string $action_attempt_id): ActionAttempt
  {
    return ActionAttempt::from_json(
      $this->seam->request(
        "GET",
        "action_attempts/get",
        query: ["action_attempt_id" => $action_attempt_id],
        inner_object: "action_attempt"
      )
    );
  }
}

final class AccessCodesClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * List Access Codes
   * @return AccessCode[]
   */
  public function list(string $device_id = ""): array
  {
    return array_map(
      fn($a) => AccessCode::from_json($a),
      $this->seam->request(
        "GET",
        "access_codes/list",
        query: ["device_id" => $device_id],
        inner_object: "access_codes"
      )
    );
  }

  public function get(string $access_code_id): AccessCode
  {
    return AccessCode::from_json(
      $this->seam->request(
        "GET",
        "access_codes/get",
        query: ["access_code_id" => $access_code_id],
        inner_object: "access_code"
      )
    );
  }

  public function create(
    string $device_id,
    string $name = null,
    string $code = null,
    string $starts_at = null,
    string $ends_at = null,
    $wait_for_access_code = null
  ): ActionAttempt|AccessCode {
    $json = [
      "device_id" => $device_id,
    ];
    if ($name) {
      $json["name"] = $name;
    }
    if ($code) {
      $json["code"] = $code;
    }
    if ($starts_at) {
      $json["starts_at"] = $starts_at;
    }
    if ($ends_at) {
      $json["ends_at"] = $ends_at;
    }

    if (($starts_at || $ends_at) && $wait_for_access_code == null) {
      $wait_for_access_code = false;
    } elseif ($wait_for_access_code == null) {
      $wait_for_access_code = true;
    }

    // TODO future versions of the API will return the AccessCode immediately
    // return AccessCode::from_json($this->seam->request(
    //   "POST",
    //   "access_codes/create",
    //   json: $json,
    //   inner_object: 'access_code'
    // ));

    // TODO remove everything under this when API returns AccessCode immediately
    $action_attempt = ActionAttempt::from_json(
      $this->seam->request(
        "POST",
        "access_codes/create",
        json: $json,
        inner_object: "action_attempt"
      )
    );

    $time_waiting = 0.0;
    while ($action_attempt->status == "pending") {
      $action_attempt = $this->seam->action_attempts->get(
        $action_attempt->action_attempt_id
      );
      if ($time_waiting > 20.0) {
        throw new Exception("Timed out waiting for access code to be created");
      }
      $time_waiting += 0.4;
      usleep(400000); // sleep for 0.4 seconds
    }

    if ($action_attempt->status == "failed") {
      throw new Exception(
        "Failed to create access code: " . $action_attempt->error->message
      );
    }

    if (!$action_attempt->result?->access_code) {
      throw new Exception(
        "Failed to create access code: no access code returned: " .
          json_encode($action_attempt)
      );
    }
    return AccessCode::from_json($action_attempt->result->access_code);
  }
}

final class ConnectWebviewsClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  public function create($accepted_providers = [])
  {
    return ConnectWebview::from_json(
      $this->seam->request(
        "POST",
        "connect_webviews/create",
        json: [
          "accepted_providers" => $accepted_providers,
        ],
        inner_object: "connect_webview"
      )
    );
  }

  /**
   * List Connect Webviews
   * @return ConnectWebview[]
   */
  public function list(string $device_id = ""): array
  {
    return array_map(
      fn($a) => ConnectWebview::from_json($a),
      $this->seam->request(
        "GET",
        "connect_webviews/list",
        inner_object: "connect_webviews"
      )
    );
  }

  public function get(string $connect_webview_id): ConnectWebview
  {
    return ConnectWebview::from_json(
      $this->seam->request(
        "GET",
        "connect_webviews/get",
        query: ["connect_webview_id" => $connect_webview_id],
        inner_object: "connect_webview"
      )
    );
  }
}

final class ConnectedAccountsClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * List Connected Accounts
   * @return ConnectedAccount[]
   */
  public function list(): array
  {
    return array_map(
      fn($a) => ConnectedAccount::from_json($a),
      $this->seam->request(
        "GET",
        "connected_accounts/list",
        inner_object: "connected_accounts"
      )
    );
  }

  public function get(string $connected_account_id): ConnectedAccount
  {
    return ConnectedAccount::from_json(
      $this->seam->request(
        "GET",
        "connected_accounts/get",
        query: ["connected_account_id" => $connected_account_id],
        inner_object: "connected_account"
      )
    );
  }
}

final class EventsClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * Get Event
   */
  public function get(string $event_id): Event|null
  {
    $event = Event::from_json(
      $this->seam->request(
        "GET",
        "events/get",
        query: ["event_id" => $event_id],
        inner_object: "event"
      )
    );
    return $event;
  }

  /**
   * List Events
   * @return Event[]
   */
  public function list(string $since): array
  {
    return array_map(
      fn($d) => Event::from_json($d),
      $this->seam->request(
        "GET",
        "events/list",
        query: [
          "since" => $since,
        ],
        inner_object: "events"
      )
    );
  }
}

final class LocksClient
{
  private SeamClient $seam;
  public function __construct(SeamClient $seam)
  {
    $this->seam = $seam;
  }

  /**
   * Get Lock
   */
  public function get(string $device_id): Device|null
  {
    $device = Device::from_json(
      $this->seam->request(
        "GET",
        "locks/get",
        query: ["device_id" => $device_id],
        inner_object: "lock"
      )
    );
    return $device;
  }

  /**
   * List Events
   * @return Device[]
   */
  public function list(string $connected_account_id = null): array
  {
    return array_map(
      fn($d) => Device::from_json($d),
      $this->seam->request(
        "GET",
        "locks/list",
        query: [
          "connected_account_id" => $connected_account_id,
        ],
        inner_object: "locks"
      )
    );
  }

  public function lock_door(string $device_id): ActionAttempt
  {
    return ActionAttempt::from_json(
      $this->seam->request(
        "POST",
        "locks/lock_door",
        json: [
          "device_id" => $device_id,
        ],
        inner_object: "action_attempt"
      )
    );
  }

  public function unlock_door(string $device_id): ActionAttempt
  {
    return ActionAttempt::from_json(
      $this->seam->request(
        "POST",
        "locks/unlock_door",
        json: [
          "device_id" => $device_id,
        ],
        inner_object: "action_attempt"
      )
    );
  }
}