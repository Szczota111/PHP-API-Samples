<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom API exceptions for better error handling
 */
class ApiException extends Exception {}
class ApiConfigurationException extends ApiException {}
class ApiRequestException extends ApiException
{
    protected $statusCode;
    protected $responseBody;

    public function __construct($message, $statusCode = 0, $responseBody = '', $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
class ApiAuthenticationException extends ApiRequestException {}

class Api
{
    private $token = "";
    private $url = "";
    private $username = "";
    private $password = "";
    private $lang = "";
    private $session = null;

    public function __construct($url, $username, $password, $lang)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->lang = $lang;
        $this->session = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'Connection' => 'keep-alive'
            ],
            'timeout' => 30,
            'retry' => function (
                $retries,
                RequestException $exception
            ) {
                if ($retries >= 7) {
                    return false;
                }
                if ($exception->getResponse() && in_array($exception->getResponse()->getStatusCode(), [502, 503, 504])) {
                    return true;
                }
                return false;
            }
        ]);
    }

    private function getToken()
    {
        if ($this->token !== "") {
            return $this->token;
        }

        // generate md5 sum of username and url
        $md5sum = md5($this->username . $this->url);

        // if there is token.txt file, read it
        $tokenFile = __DIR__ . "/token_" . $md5sum . ".txt";
        if (file_exists($tokenFile)) {
            $this->token = file_get_contents($tokenFile);
            // check token by getting user info
            try {
                $response = $this->get($this->url . "/api/auth/user");
                if ($response->getStatusCode() !== 200) {
                    $this->token = "";
                }
            } catch (RequestException $e) {
                $this->token = "";
            } catch (ApiRequestException $e) {
                $this->token = "";
            }
        }

        // if token is not empty, return it
        if ($this->token !== "") {
            return $this->token;
        }

        // if url, username or password is empty, throw exception
        if ($this->url === "") {
            throw new ApiConfigurationException("No URL specified");
        }
        if ($this->username === "") {
            throw new ApiConfigurationException("No username specified");
        }
        if ($this->password === "") {
            throw new ApiConfigurationException("No password specified");
        }

        // if token is empty, get it from the API
        $args = [
            "username" => $this->username,
            "password" => $this->password,
            "useragent" => "api request"
        ];
        $response = $this->session->post("/api/auth/login", [
            'json' => $args
        ]);
        if ($response->getStatusCode() !== 200) {
            $responseBody = $response->getBody()->getContents();
            throw new ApiAuthenticationException(
                "Invalid credentials",
                $response->getStatusCode(),
                $responseBody,
                null
            );
        }

        $token = $response->getBody()->getContents();
        $token = json_decode($token, true);
        $this->token = $token['token']['token_type'] . " " . $token['token']['access_token'];

        // save token to token.txt file
        file_put_contents($tokenFile, $this->token);

        return $this->token;
    }

    public function __call($name, $arguments)
    {
        $headers = [
            "Authorization" => $this->getToken(),
            "Accept-Language" => $this->lang,
            "Accept" => "application/json"
        ];
        $timeout = 30;

        // merge headers with kwargs headers
        if (isset($arguments[1]['headers'])) {
            $headers = array_merge($headers, $arguments[1]['headers']);
            unset($arguments[1]['headers']);
        }

        // merge headers with kwargs headers
        if (isset($arguments[1]['timeout'])) {
            $timeout = $arguments[1]['timeout'];
            unset($arguments[1]['timeout']);
        }

        if (isset($arguments[0]['url'])) {
            // if url in kwargs doesn't start with http
            if (strpos($arguments[0]['url'], "http") === 0) {
                $url = $arguments[0]['url'];
            } else {
                $url = $this->url . $arguments[0]['url'];
            }
            unset($arguments[0]['url']);
        } else {
            // if url in kwargs doesn't start with http
            if (strpos($arguments[0], "http") === 0) {
                $url = $arguments[0];
            } else {
                $url = $this->url . $arguments[0];
            }
            array_shift($arguments);
        }

        try {
            $response = $this->session->$name($url, [
                'headers' => $headers,
                'timeout' => $timeout,
                'json' => $arguments[0] ?? []
            ]);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $requestBody = $e->getRequest() ? $e->getRequest()->getBody() : '';

            throw new ApiRequestException(
                "API request failed for {$url}: " . $e->getMessage(),
                $statusCode,
                json_encode([
                    'url' => $url,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody,
                    'original_message' => $e->getMessage()
                ]),
                $e
            );
        }

        return $response;
    }

    private function __first($method, $endpoint, ...$args)
    {
        //add limit=1 to endpoint if not present
        if (strpos($endpoint, "limit=") === false) {
            if (strpos($endpoint, "?") === false) {
                $endpoint .= "?limit=1";
            } else {
                $endpoint .= "&limit=1";
            }
        }
        $response = $this->$method($endpoint, ...$args);
        if ($response->getStatusCode() !== 200) {
            $responseBody = $response->getBody()->getContents();
            $requestBody = $response->getRequest()->getBody();
            throw new ApiRequestException(
                "API request failed for {$endpoint}",
                $response->getStatusCode(),
                json_encode([
                    'endpoint' => $endpoint,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody
                ]),
                null
            );
        }
        $data = json_decode($response->getBody()->getContents(), true);
        if (!isset($data["data"]) || !is_array($data["data"])) {
            trigger_error("API did not return an array of results: " . json_encode($data), E_USER_ERROR);
        }
        if (empty($data["data"])) {
            return null;
        }
        return $data["data"][0] ?? null;
    }

    private function __all($method, $endpoint, ...$args)
    {
        $response = $this->$method($endpoint, ...$args);
        if ($response->getStatusCode() !== 200) {
            $responseBody = $response->getBody()->getContents();
            $requestBody = $response->getRequest()->getBody();
            throw new ApiRequestException(
                "API request failed for {$endpoint}",
                $response->getStatusCode(),
                json_encode([
                    'endpoint' => $endpoint,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody
                ]),
                null
            );
        }
        $data = json_decode($response->getBody()->getContents(), true);
        $ret = $data["data"];

        if (!isset($data["meta"]) || !isset($data["meta"]["per_page"])) {
            return $ret;
        }

        $per_page = $data["meta"]["per_page"];

        $with_trashed = false;
        $only_trashed = false;
        if (strpos($endpoint, "with_trashed=") !== false) {
            $with_trashed = true;
        }
        if (strpos($endpoint, "only_trashed=") !== false) {
            $only_trashed = true;
        }

        while (isset($data["links"]["next"]) && $data["links"]["next"] !== null) {
            $response = $this->$method(
                $data["links"]["next"]
                    . "&limit="
                    . $per_page
                    . ($with_trashed ? "&with_trashed=true" : "")
                    . ($only_trashed ? "&only_trashed=true" : ""),
                ...$args
            );
            if ($response->getStatusCode() !== 200) {
                $responseBody = $response->getBody()->getContents();
                $requestBody = $response->getRequest()->getBody();
                throw new ApiRequestException(
                    "API request failed for pagination {$endpoint}",
                    $response->getStatusCode(),
                    json_encode([
                        'endpoint' => $endpoint,
                        'request_body' => (string)$requestBody,
                        'response_body' => $responseBody
                    ]),
                    null
                );
            }
            $data = json_decode($response->getBody()->getContents(), true);
            $ret = array_merge($ret, $data["data"]);
        }
        return $ret;
    }

    public function create($endpoint, ...$args)
    {
        $response = $this->post($endpoint, ...$args);
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
            $responseBody = $response->getBody()->getContents();
            $requestBody = $response->getRequest()->getBody();
            throw new ApiRequestException(
                "API request failed for {$endpoint}",
                $response->getStatusCode(),
                json_encode([
                    'endpoint' => $endpoint,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody
                ]),
                null
            );
        }
        $data = json_decode($response->getBody()->getContents(), true);
        return $data["data"] ?? null;
    }

    public function update($endpoint, ...$args)
    {
        $response = $this->put($endpoint, ...$args);
        if ($response->getStatusCode() !== 200) {
            $responseBody = $response->getBody()->getContents();
            $requestBody = $response->getRequest()->getBody();
            throw new ApiRequestException(
                "API request failed for {$endpoint}",
                $response->getStatusCode(),
                json_encode([
                    'endpoint' => $endpoint,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody
                ]),
                null
            );
        }
        $data = json_decode($response->getBody()->getContents(), true);
        return $data["data"] ?? null;
    }

    public function first($endpoint, ...$args)
    {
        return $this->getFirst($endpoint, ...$args);
    }

    public function getFirst($endpoint, ...$args)
    {
        return $this->__first("get", $endpoint, ...$args);
    }

    public function record($endpoint, ...$args)
    {
        return $this->getRecord($endpoint, ...$args);
    }

    public function getRecord($endpoint, ...$args)
    {
        $response = $this->get($endpoint, ...$args);
        if ($response->getStatusCode() !== 200) {
            $responseBody = $response->getBody()->getContents();
            $requestBody = $response->getRequest()->getBody();
            throw new ApiRequestException(
                "API request failed for {$endpoint}",
                $response->getStatusCode(),
                json_encode([
                    'endpoint' => $endpoint,
                    'request_body' => (string)$requestBody,
                    'response_body' => $responseBody
                ]),
                null
            );
        }
        $data = json_decode($response->getBody()->getContents(), true);
        return $data["data"] ?? null;
    }

    public function all($endpoint, ...$args)
    {
        return $this->getAll($endpoint, ...$args);
    }

    public function getAll($endpoint, ...$args)
    {
        return $this->__all("get", $endpoint, ...$args);
    }

    public function searchFirst($endpoint, ...$args)
    {
        return $this->__first("post", $endpoint . "/search", ...$args);
    }

    public function searchAll($endpoint, ...$args)
    {
        return $this->__all("post", $endpoint . "/search", ...$args);
    }

    public function search($endpoint, ...$args)
    {
        return $this->post($endpoint . "/search", ...$args);
    }

    public function throwErr($response)
    {
        $uri = $response->getRequest()->getUri();
        $requestBody = $response->getRequest()->getBody();
        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody()->getContents();

        throw new ApiRequestException(
            "API error for {$uri}",
            $statusCode,
            json_encode([
                'uri' => (string)$uri,
                'request_body' => (string)$requestBody,
                'response_body' => $responseBody
            ]),
            null
        );
    }
}
