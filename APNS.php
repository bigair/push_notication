<?php 
/** 
 * Apple Push Notification Service 
 *
 * Example usage
 * -------------------------------------
 * $apn = new Push\APNS();
 * $apn->setDevices($deviceToken);
 * $apn->send($message, $data);
 * -------------------------------------
 *
 * $key, $_cert 要跟 apple 申請
 *
 */ 
class APNS
{
    var $devices = [];

    var $key = '111111';
    var $url = 'ssl://gateway.push.apple.com:2195';

    private $_cert = '/product/dispushcert.pem';

    public function setDev() 
    {
        $this->url = 'ssl://gateway.sandbox.push.apple.com:2195';
        $this->_cert = '/product/devpushcert.pem';
    }

    public function setDevices($deviceIds) 
    {
        if(is_array($deviceIds)) {
            $this->devices = $deviceIds;
        } else {
            $this->devices = array($deviceIds);
        }
    }

    public function send($message, $data = false) {

        if(!is_array($this->devices) || count($this->devices) == 0){
            $this->error("No devices set");
        }

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->_cert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->key);

        // Open a connection to the APNS server
        $fp = stream_socket_client(
            $this->url, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp) 
            $this->error("Failed to connect: $err $errstr");

        // Create the payload body
        $body['aps'] = ['alert' => $message, 'badge' => 1, 'sound' => 'default'];

        if(is_array($data)){
            foreach ($data as $key => $value) {
                $body['aps'][$key] = $value;
            }
        }

        // Encode the payload as JSON
        $payload = json_encode($body);

        // Build the binary notification
        foreach ($this->devices as $device_token) {
            
            $msg = chr(0) . pack('n', 32) . pack('H*', $device_token) . pack('n', strlen($payload)) . $payload;
            // $msg = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device_token)) . chr(0) . chr(strlen($payload)) . $payload;

            $result = fwrite($fp, $msg, strlen($msg));
        }

        if (!$result)
            $this->error('Message not delivered' . PHP_EOL);

        // Close the connection to the server
        fclose($fp);
    }

    public function error($message)
    {
        echo $message . PHP_EOL;
        exit(1);
    }
}
