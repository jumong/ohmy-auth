<?php namespace ohmy\Auth1\Security;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

class Signature {

    private $method;
    private $url;
    private $params;
    private $type;
    private $oauth_consumer_secret;
    private $oauth_token_secret;
    private $debug = true;

    public function __construct(
        $method,
        $url,
        $oauth_params=array(),
        $params=array(),
        $headers=array()
    ) {

        $url = parse_url($url);
        $params = array_merge($oauth_params, $params);
        parse_str($url['query'], $_params);
        $params = array_merge($params, $_params);

        # set consumer/token secrets
        $this->oauth_consumer_secret = $params['oauth_consumer_secret'];
        $this->oauth_token_secret = (isset($params['oauth_token_secret'])) ? $params['oauth_token_secret'] : '';

        unset($params['oauth_consumer_secret']);
        unset($params['oauth_token_secret']);

        # sort params
        ksort($params);

        # constructor
        $this->method = $method;
        $this->url = $url['scheme'].'://'.$url['host'].$url['path'];
        $this->params = $params;
        $this->type = $params['oauth_signature_method'];

    }

    public function __toString() {

        $base_string = $this->getBaseString();
        $signing_key = $this->getSigningKey();
        $oauth_signature = null;
        $output = array();

        switch($this->type) {
            case 'PLAINTEXT':
                break;
            case 'HMAC-SHA1':
                $oauth_signature = base64_encode(hash_hmac(
                    'sha1',
                    $base_string,
                    $signing_key,
                    true
                ));
                break;
            case 'RSA-SHA1':
                break;
            default:
        }

        if ($this->debug) error_log("OAUTH_SIGNATURE: $oauth_signature");

        foreach($this->params as $key => $value) {
            array_push($output, "$key=\"". $value ."\"");
        }

        array_push(
            $output,
            'oauth_signature="'. rawurlencode($oauth_signature) .'"'
        );

        # sort($output);
        $output = 'OAuth '.implode(', ', $output);

        # return $oauth_signature;
        return $output;
    }

    private function getQueryString() {
        $output = array();
        foreach($this->params as $key => $value) {
            array_push($output, rawurlencode($key).'='.rawurlencode($value));
        }
        return implode('&', $output);
    }

    private function getBaseString() {

        $output =  $this->method
                   .'&'
                   .rawurlencode($this->url)
                   .'&'
                   .rawurlencode($this->getQueryString());

        if ($this->debug) error_log("SIGNATURE BASE STRING: $output");
        return $output;
    }

    private function getSigningKey() {
        $output =  $this->oauth_consumer_secret
                   .'&'
                   .$this->oauth_token_secret;

        if ($this->debug) error_log("SIGNING KEY: $output");
        return $output;
    }

}