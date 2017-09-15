<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\APIRequests as APIRequests;
use \Due\Due as Due;
// ---------------------------------------------------------------------------->
/**
 * Tokenize Class
 *
 * @package Due
 */
class Tokenize
{
    // ------------------------------------------------------------------------>
    /**
     * Get Class params
     *
     * @param array $arg_params
     * @return array
     */
    protected static function getParams(array $arg_params)
    {
        $return = array();
        //validate params
        $data = array(
            'token',
            'email'
        );
        foreach ($data as $key) {
            if(!empty($arg_params[$key])){
                $return[$key] = $arg_params[$key];
            }
        }

        return $return;
    }
    // ------------------------------------------------------------------------>
    /**
     * Charge A Card
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function doTokenizeCard($arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        $data['source_id'] = Due::getAppId();
        $data['rail_type'] = Due::getRailType();

        //submit to api
        $token_data = APIRequests::request(
            '/payments/tokenize',
            APIRequests::METHOD_POST,
            $data
        );

        //return response
        return self::toObj($token_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Tokenize A Card
     *
     * @param array $arg_params
     * @return null|object
     */
    public static function card($arg_params)
    {
        return self::doTokenizeCard($arg_params);
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $token_data
     * @return null|object
     */
    public static function toObj($token_data)
    {
        if(!empty($token_data['token'])){
            return (object) $token_data['token'];
        }

        return null;
    }
}