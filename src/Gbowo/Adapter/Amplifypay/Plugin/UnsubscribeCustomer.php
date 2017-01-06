<?php

namespace Gbowo\Adapter\Amplifypay\Plugin;

use Gbowo\Plugin\AbstractPlugin;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Gbowo\Adapter\Amplifypay\Traits\KeyVerifier;
use Gbowo\Exception\InvalidHttpResponseException;
use Gbowo\Adapter\AmplifyPay\Exception\TransactionVerficationFailedException;

class UnsubscribeCustomer extends AbstractPlugin
{

    use KeyVerifier;

    const UN_SUBSCRIBE_LINK = "/subscription/cancel";

    /**
     * It's with a double `ll`
     * @see https://amplifypay.com/developers Unsubscribe a customer from plan
     * @var string
     */
    const STATUS_DESCRIPTION_SUCCESS = "Successfull Request";

    const STATUS_DESCRIPTION_FAILURE = "Unsuccessfull Request";

    protected $baseUrl;

    protected $apiKeys;

    public function __construct(string $baseUrl, array $apiKeys)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKeys = $apiKeys;
    }

    public function getPluginAccessor() :string
    {
        return "unsubcribeCustomerFromPlan";
    }

    /**
     * @param array ...$args
     * @return mixed
     * @throws \Gbowo\Adapter\AmplifyPay\Exception\TransactionVerficationFailedException
     * @throws \Gbowo\Exception\InvalidHttpResponseException if we don't get a 200 Status code
     */
    public function handle(...$args)
    {

        $link = $this->baseUrl . self::UN_SUBSCRIBE_LINK;

        $response = $this->adapter->getHttpClient()
            ->post($link, [
                'body' => json_encode(array_merge($this->apiKeys, $args[0]))
            ]);

        if (200 !== $response->getStatusCode()) {
            throw new InvalidHttpResponseException(
                "Expected 200 HTTP status , got {$response->getStatusCode()} instead"
            );
        }

        $response = json_decode($response->getBody(), true);

        $validated = false;

        if (strcmp($response['StatusDesc'], self::STATUS_DESCRIPTION_SUCCESS) === 0) {
            $validated = true;
        }

        if (false === $validated) {
            throw new TransactionVerficationFailedException(self::STATUS_DESCRIPTION_FAILURE);
        }

        //not consistent, some transaction with the Amplifypay API returns `ApiKey`, some `apiKey`.

        $this->verifyKeys($response['apiKey'], $this->apiKeys['apiKey']);

        return $response;
    }
}
