<?php

namespace FiberPay;

class FiberPayClient {

    private $version = '1.0';
    private $apiUrl;

    protected $apiKey;
    protected $apiSecret;

    public function __construct($apiKey, $apiSecret, $testServer = false) {
        $this->apiUrl = $testServer ? 'https://apitest.fiberpay.pl' : 'https://api.fiberpay.pl';

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function call($httpMethod, $uri, $data = null){
        $headers = $this->createHeaders($httpMethod, $uri, $data);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl . $uri);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        if ($httpMethod === 'post'){
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        } else if ($httpMethod === 'put') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        }

        $response = curl_exec($curl);

        if (curl_errno($curl))
            $errorMsg = curl_error($curl);

        curl_close($curl);

        if (isset($errorMsg))
            throw new \Exception($errorMsg);

        return $response;
    }

    private function createHeaders($httpMethod, $uri, $data = null){
        $nonce = $this->nonce();

        $route = implode(' ', [strtoupper($httpMethod), $uri]);

        $data = empty($data) ? '' : json_encode($data);
        $signature = $this->signature($route, $nonce, $this->apiKey, $data, $this->apiSecret);

        $headers = [
            "Content-Type: application/json",
            "X-API-Key: $this->apiKey",
            "X-API-Nonce: $nonce",
            "X-API-Route: $route",
            "X-API-Signature: $signature",
        ];

        return $headers;
    }

    protected function nonce() {
        $nonce = explode(' ', microtime());
        $nonce = $nonce[1] . substr($nonce[0], 2);
        return $nonce;
    }

    private function signature($route, $nonce, $apiKey, $data, $apiSecret) {
        $toBeSigned = implode('', [$route, $nonce, $apiKey, $data]);
        return hash_hmac('sha512', $toBeSigned, $apiSecret);
    }

    private function addCallbackData(array $data, string $callbackUrl = null, $callbackParams = null) {
        if(!empty($callbackUrl)) {
            $data['callbackUrl'] = $callbackUrl;
            if(!empty($callbackParams))
                $data['callbackParams'] = $callbackParams;
        }

        return $data;
    }

    //FiberSplit methods

    public function createSplit($currency = 'PLN') {
        $data['currency'] = $currency;

        $uri = "/$this->version/orders/split";

        return $this->call('post', $uri, $data);
    }

    public function addSplitItem($orderCode, $toName, $toIban, $description, $amount,
                                 $currency = 'PLN', $callbackUrl = null, $callbackParams = null) {

        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'parentCode' => $orderCode,
            'toName' => $toName,
            'toIban' => $toIban,
            'description' => $description,
        ];

        $data = $this->addCallbackData($data, $callbackUrl, $callbackParams);

        $uri = "/$this->version/orders/split/item";

        return $this->call('post', $uri, $data);
    }

    public function endDefinitionOfSplit($orderCode) {
        $uri = "/$this->version/orders/split/$orderCode/define";

        return $this->call('put', $uri);
    }

    public function getSplit($orderCode) {
        $uri = "/$this->version/orders/split/$orderCode";

        return $this->call('get', $uri);
    }

    public function getSplitItem($orderItemCode) {
        $uri = "/$this->version/orders/split/item/$orderItemCode";

        return $this->call('get', $uri);
    }

    //FiberCollect methods

    public function createCollect($toName, $toIban, $currency = 'PLN') {
        $data = [
            'currency' => $currency,
            'toName' => $toName,
            'toIban' => $toIban,
        ];

        $uri = "/$this->version/orders/collect";

        return $this->call('post', $uri, $data);
    }

    public function addCollectItem($orderCode, $description, $amount, $currency = 'PLN',
                                   $callbackUrl = null, $callbackParams = null) {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'parentOrder' => $orderCode,
        ];

        $data = $this->addCallbackData($data, $callbackUrl, $callbackParams);

        $uri = "/$this->version/orders/collect/item";

        return $this->call('post', $uri, $data);
    }

    public function getCollectOrderInfo($orderCode) {
        $uri = "/$this->version/orders/collect/$orderCode";

        return $this->call('get', $uri);
    }

    public function getCollectOrderItemInfo($orderItemCode) {
        $uri = "/$this->version/orders/collect/item/$orderItemCode";

        return $this->call('get', $uri);
    }

    //FiberDirect methods

    public function createDirect($toName, $toIban, $description, $amount,
                                 $currency = 'PLN', $callbackUrl = null, $callbackParams = null) {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'toName' => $toName,
            'toIban' => $toIban,
            'description' => $description,
        ];

        $data = $this->addCallbackData($data, $callbackUrl, $callbackParams);

        $uri = "/$this->version/orders/direct";

        return $this->call('post', $uri, $data);
    }

    public function getDirectOrderInfo($orderCode) {
        $uri = "/$this->version/orders/direct/$orderCode";

        return $this->call('get', $uri);
    }

    //FiberForward methods

    public function createForward($toName, $toIban, $description, $amount,
                                 $currency = 'PLN', $callbackUrl = null, $callbackParams = null) {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'toName' => $toName,
            'toIban' => $toIban,
            'description' => $description,
        ];

        $data = $this->addCallbackData($data, $callbackUrl, $callbackParams);

        $uri = "/$this->version/orders/forward";

        return $this->call('post', $uri, $data);
    }

    public function getForwardOrderInfo($orderCode) {
        $uri = "/$this->version/orders/forward/$orderCode";

        return $this->call('get', $uri);
    }

}