<?php
namespace Kunnu\Dropbox;

use Kunnu\Dropbox\Http\Clients\DropboxHttpClientInterface;

class DropboxClient
{
    /**
     * Dropbox API Root URL.
     *
     * @const string
     */
    const BASE_PATH = 'https://api.dropboxapi.com/2';

    /**
     * Dropbox API Content Root URL.
     *
     * @const string
     */
    const CONTENT_PATH = 'https://content.dropboxapi.com/2';

    /**
     * DropboxHttpClientInterface Implementation
     *
     * @var \Kunnu\Dropbox\Http\Clients\DropboxHttpClientInterface
     */
    protected $httpClient;

    /**
     * Create a new DropboxClient instance
     *
     * @param DropboxHttpClientInterface $httpClient
     */
    public function __construct(DropboxHttpClientInterface $httpClient)
    {
        //Set the HTTP Client
        $this->setHttpClient($httpClient);
    }

    /**
     * Get the HTTP Client
     *
     * @return \Kunnu\Dropbox\Http\Clients\DropboxHttpClientInterface $httpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set the HTTP Client
     *
     * @param \Kunnu\Dropbox\Http\Clients\DropboxHttpClientInterface $httpClient
     *
     * @return \Kunnu\Dropbox\DropboxClient
     */
    public function setHttpClient(DropboxHttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * Get the API Base Path.
     *
     * @return string API Base Path
     */
    public function getBasePath()
    {
        return static::BASE_PATH;
    }

    /**
     * Get the API Content Path.
     *
     * @return string API Content Path
     */
    public function getContentPath()
    {
        return static::CONTENT_PATH;
    }

    /**
     * Get the Authorization Header with the Access Token.
     *
     * @param string $access_token Access Token
     *
     * @return array Authorization Header
     */
    protected function buildAuthHeader($access_token = "")
    {
        return ['Authorization' => 'Bearer '. $access_token];
    }

    /**
     * Get the Content Type Header.
     *
     * @param string $contentType Request Content Type
     *
     * @return array Content Type Header
     */
    protected function buildContentTypeHeader($contentType = "")
    {
        return ['Content-Type' => $contentType];
    }

    /**
     * Build URL for the Request
     *
     * @param string $endpoint Relative API endpoint
     * @param string $type Endpoint Type
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#formats Request and response formats
     *
     * @return string The Full URL to the API Endpoints
     */
    protected function buildUrl($endpoint = '', $type = 'api')
    {
        //Get the base path
        $base = $this->getBasePath();

        //If the endpoint type is 'content'
        if ($type === 'content') {
            //Get the Content Path
            $base = $this->getContentPath();
        }

        //Join and return the base and api path/endpoint
        return $base . $endpoint;
    }

    /**
     * Send the Request to the Server and return the Response
     * @param  DropboxRequest $request
     *
     * @return \Kunnu\Dropbox\DropboxResponse
     *
     * @throws \Kunnu\Dropbox\Exceptions\DropboxClientException
     */
    public function sendRequest(DropboxRequest $request)
    {
        //Method
        $method = $request->getMethod();

        //Prepare Request
        list($url, $headers, $requestBody) = $this->prepareRequest($request);

        //Send the Request to the Server through the HTTP Client
        //and fetch the raw response as DropboxRawResponse
        $rawResponse = $this->getHttpClient()->send($url, $method, $requestBody, $headers);

        //Create DropboxResponse from DropboxRawResponse
        $dropboxResponse = new DropboxResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
            );

        //Return the DropboxResponse
        return $dropboxResponse;
    }

    /**
     * Prepare a Request before being sent to the HTTP Client
     * @param  DropboxResponse $request
     *
     * @return array                   [Request URL, Request Headers, Request Body]
     */
    protected function prepareRequest(DropboxRequest $request)
    {
        //Build URL
        $url = $this->buildUrl($request->getEndpoint(), $request->getEndpointType());

        //File needs to be uploaded
        if($request->hasFile()) {
            //Dropbox requires the file metadata to be passed
            //through the 'Dropbox-API-Arg' header
            $request->setHeaders(['Dropbox-API-Arg' => json_encode($request->getParams())]);
            $request->setContentType("application/octet-stream");

            //Request Body
            $requestBody = $request->getStreamBody()->getBody();
        } else {
            //Request Body
            $requestBody = $request->getJsonBody()->getBody();
        }

        //Build headers
        $headers = array_merge(
            $this->buildAuthHeader($request->getAccessToken()),
            $this->buildContentTypeHeader($request->getContentType()),
            $request->getHeaders()
            );

        //Return the URL, Headers and Request Body
        return [$url, $headers, $requestBody];
    }


}