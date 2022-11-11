<?php

namespace mauriziocingolani\yii2componentszoom;

use yii\base\Component;

/**
 * Componente per la gestione delle funzionalità Zoom.
 * @author Maurizio Cingolani <mauriziocingolani74@gmail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version 1.0.6
 */
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

    /**
     * @see https://marketplace.zoom.us/docs/api-reference/zoom-api/users/users
     */
    public function getUsers($asArray = false) {
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
        $data = json_decode($response);
        if ($asArray === true) :
            $users = [];
            foreach ($data->users as $user) :
                $users[$user->id] = $user->email;
            endforeach;
            asort($users);
            return $users;
        endif;
        return $data;
    }

    /**
     * @see https://marketplace.zoom.us/docs/api-reference/zoom-api/cloud-recording/recordingslist
     */
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

    /**
     * @see https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingcreate
     */
    public function createMeeting($userid, $params) {
        $curl = curl_init();
        $params2 = array_merge([
            'type' => 2,
            'timezone' => 'Europe/Rome',
            'default_password' => true,
                ], $params);
        $params2['settings'] = array_merge($params['settings'] ?? [], [
            'host_video' => true,
            'participant_video' => true,
            'audio' => 'voip',
        ]);
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::URL . "/users/$userid/meetings",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params2),
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

    /**
     * @see https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/meetings
     */
    public function getMeetings($userid) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::URL . "/users/$userid/meetings",
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

    /**
     * @see https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meeting
     */
    public function getMeeting($meetingid) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::URL . "/meetings/$meetingid",
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
