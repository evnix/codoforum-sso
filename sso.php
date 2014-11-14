<?php

/**
 * 
 * @CODOLICENSE
 */

class codoforum_sso {

    private $client_id;
    private $secret;
    private $timeout;

    public function __construct($settings) {
        
        $this->client_id = $settings['client_id'];
        $this->secret = $settings['secret'];
        $this->timeout = $settings['timeout'];
        
    }
    
    public function gateway() {

        if (!isset($_GET['client_id'])) {

            return array('error' => 'client_id parameter is missing');
        } else if ($_GET['client_id'] != $this->client_id) {

            return array('error' => 'client_id does not match');
        } else if (!isset($_GET['timestamp']) || !is_numeric($_GET['timestamp'])) {

            return array('error' => 'The timestamp provided is invalid or missing');
        } else if (!isset($_GET['token'])) {

            return array('error' => 'No token provided');
        } elseif (abs($_GET['timestamp'] - time()) > $this->timeout) {

            return array('error' => 'The timestamp is invalid.');
        } else {
            // Make sure the timestamp hasn't timed out.
            $token = md5($_GET['timestamp'] . $this->secret);
            if ($token != $_GET['token']) {
                return array('error' => 'Invalid token');
            }
        }
    }

    public function output_jsonp($data) {

        $this->user = $data;
        
        $res = $this->gateway();
                
        if(isset($res['error'])) {
            
            $data = $res;
        }
        
                
        $user = json_encode($data);
        $token = md5(urlencode($user) . $this->secret . $_GET['timestamp']);
        
        $resp = json_encode(array_merge($data, array("token" => $token)));

        if (isset($_GET['callback'])) {

	  if (preg_match('/\W/', $_GET['callback'])) {
	    // if $_GET['callback'] contains a non-word character,
	    // this could be an XSS attack.
	    header('HTTP/1.1 400 Bad Request');
	    exit();
	  }
	  
	  header('Content-type: application/javascript; charset=utf-8');
	  print sprintf('%s(%s);', $_GET['callback'], $resp);        
        }
    }
}
