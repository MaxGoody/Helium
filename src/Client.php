<?php
namespace Helium;

use OutOfRangeException;
use Helium\Exceptions\RequestException;
use Helium\Exceptions\ResponseException;

/**
 * Client
 * @package Helium
 * @author Maxim Alexeev
 * @license ISC
 */
class Client
{
    
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $auth_key;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var array
     */
    private $method_parts = [];

    public function __construct(int $id, string $auth_key)
    {
        $this->id = $id;
        $this->auth_key = $auth_key;
        $this->resource = curl_init();
    }
    
    /**
     * @param string $name
     * @throws OutOfRangeException
     */
    public function __get(string $name)
    {
        if (count($this->method_parts) > 0) {
            throw new OutOfRangeException('The method is characterized by only one controller!');
        }

        $this->method_parts[] = $name;
        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws RequestException
     * @throws ResponseException
     */
    public function __call(string $name, array $arguments)
    {
        $this->method_parts[] = $name;
        array_unshift($arguments, implode('.', $this->method_parts));
        $this->method_parts = [];
        return call_user_func_array([$this, 'call'], $arguments);
    }

    /**
     * @param string $method
     * @param array $options
     * 
     * @return mixed
     * @throws RequestException
     * @throws ResponseException
     */
    public function call(string $method, array $options = [])
    {
        $options['method'] = $method;
        $options['user'] = $this->id;
        $options['key'] = $this->auth_key;

        curl_reset($this->resource);
        curl_setopt_array($this->resource, [
            CURLOPT_URL => '109.234.156.25'.rand(0, 3).'/prison/universal.php',
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $options
        ]);

        $response = curl_exec($this->resource);
        if ($response === false) {
            throw new RequestException(curl_error($this->resource), curl_errno($this->resource));
        }

        if ($response == '<result>0</result>') {
            throw new ResponseException('Invalid credentials!');
        }

        $data = strpos($response, '<') === 0 ? simplexml_load_string($response) : json_decode($response, true);
        if (is_object($data) && isset($data->error)) {
            $error = $data->error;
            throw new ResponseException($error->msg, (Int)$error->code);
        }
        
        return $data;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    /**
     * @param string $auth_key
     */
    public function setAuthKey(string $auth_key): void
    {
        $this->auth_key = $auth_key;
    }

    public function __destruct()
    {
        curl_close($this->resource);
    }
}
