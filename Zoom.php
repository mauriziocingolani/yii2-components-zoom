<?php

namespace mauriziocingolani\yii2componentszoom;

use yii\base\Component;

class Zoom extends Component {

    const URL = 'https://api.zoom.us/v2';
    const STRINGS_ARRAY = ['+', '/', '='];
    const STRINGS_ARRAY_REPLACE = ['-', '_', ''];

    public $token;
    public $apiKey;
    public $secret;

    public function init() {

        parent::init();
        if (!$this->token && !($this->apiKey && $this->secret))
            throw new \yii\base\InvalidConfigException(__CLASS__ . ': either the $token attribute or the $apikey and $secret attributes must be set.');
    }

    public function getUsers() {
        $curl = curl_init();
        $params = [
            'status' => 'active',
            'page_size' => 30,
            'page_number' => 1,
        ];
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::URL . "/users?" . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . ($this->_getJWT() ?? $this->token),
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            return $err;
        return json_decode($response);
    }

    public function getRecordings($userid) {
        $curl = curl_init();
        $params = [
            'page_size' => 30,
            'from' => '1970-01-01',
            'to' => date('Y-m-d'),
        ];
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::URL . "/users/$userid/recordings?" . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . ($this->_getJWT() ?? $this->token),
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            return $err;
        return json_decode($response);
    }

    private function _getJWT() {
        if (!$this->apiKey || !$this->secret)
            return null;
        $base64UrlHeader = str_replace(self::STRINGS_ARRAY, self::STRINGS_ARRAY_REPLACE, base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])));
        $base64UrlPayload = str_replace(self::STRINGS_ARRAY, self::STRINGS_ARRAY_REPLACE, base64_encode(json_encode(['iss' => $this->apiKey, 'exp' => (new \DateTimeImmutable())->modify('+5 minutes')->getTimestamp()])));
        $base64UrlSignature = str_replace(self::STRINGS_ARRAY, self::STRINGS_ARRAY_REPLACE, base64_encode(hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true)));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

}
