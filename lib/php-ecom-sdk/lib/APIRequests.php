<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\Due as Due;
// ---------------------------------------------------------------------------->
/**
 * Handle Due API Requests
 *
 * @package Due
 */
class APIRequests
{
    // ------------------------------------------------------------------------>
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    // ------------------------------------------------------------------------>
    /**
     * Due API Request
     *
     * @param string $arg_endpoint
     * @param string $arg_method
     * @param array $arg_data
     * @param array $arg_headers
     * @return array
     * @throws \Exception
     */
    public static function request(
        $arg_endpoint,
        $arg_method,
        array $arg_data = array(),
        array $arg_headers = array()
    ) {
        $return = array();
        $headers = array(
            'Accept: application/json',
            'DUE-API-KEY: '.Due::getApiKey(),
            'DUE-PLATFORM-ID: '.Due::getPlatformId(),
        );
        if($arg_method == APIRequests::METHOD_PUT){
            $headers[]= 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
        }
        $headers = array_merge($headers, $arg_headers);
        $full_url = Due::getRootPath().$arg_endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $arg_method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if(!empty($arg_data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arg_data));
        }
        $response = curl_exec($ch);
        $err = curl_error($ch);

        if (!$err) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            $return['headers'] = self::headersToArray($headers);
            $return['body'] = json_decode($body, true);
            if(!empty($return['body']['errors'][0])){
                $api_error = $return['body']['errors'][0];
                $message = (empty($api_error['message']) ? '' : $api_error['message']);
                $code = (empty($api_error['code']) ? 0 : $api_error['code']);
                throw new \Exception($message,$code);
            }
        }else{
            throw new \Exception($err);
        }

        return $return;
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert Header data to array
     *
     * @param string $header_text
     * @return array
     */
    public static function headersToArray($header_text)
    {
        $headers = array();


        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                if(!empty($line) && strpos($line, ':') !== false){
                    list ($key, $value) = explode(': ', $line);

                    $headers['headers'][] = array(
                        'key' => $key,
                        'value' => $value,
                    );
                }
            }

        return $headers;
    }
}
