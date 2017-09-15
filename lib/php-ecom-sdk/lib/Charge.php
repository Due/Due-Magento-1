<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\APIRequests as APIRequests;
use \Due\Due as Due;
// ---------------------------------------------------------------------------->
/**
 * Charge Class
 *
 * @package Due
 */
class Charge
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
            'amount',
            'currency',
            'webhook_url',
            'customer_id',
            'unique_id',
            'rtoken',
            'rdata',
            'source',
            'card_id',
            'card_hash',
            'order',
            'security_token',
            'customer_ip',
            'customer',
            'shipping',
            'metadata'
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
    public static function doCardPayment($arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        if(!empty($data['order'])){
            $data['rdata']['order'] = json_decode(json_encode($data['order']), true);
        }
        unset($data['order']);
        if(!empty($data['metadata'])){
            $data['metadata'] = json_decode(json_encode($data['metadata']),true);
        }
        $data['source_id'] = Due::getAppId();
        $data['rail_type'] = Due::getRailType();
        $data['amount'] = number_format(floatval($data['amount']), 2, '.', '');

        if(
            !empty($data['webhook_url']) &&
            (!preg_match("%^(http|https)://\S+%", $data['webhook_url']) ||
                filter_var($data['webhook_url'], FILTER_VALIDATE_URL) === false)
        ){
            throw new \Exception('invalid webhook url given',4040001);
        }

        //submit to api
        $customer_data = APIRequests::request(
            '/ecommerce/payments/card',
            APIRequests::METHOD_POST,
            array('payload'=>$data)
        );

        //return response
        return self::toObj($customer_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Charge A Customer
     *
     * @param array $arg_params
     * @return null|object
     */
    public static function card($arg_params)
    {
        return self::doCardPayment($arg_params);
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $transactions_data
     * @return null|object
     */
    public static function toObj($transactions_data)
    {
        if(!empty($transactions_data['transactions'][0]['id'])){
            return (object) $transactions_data['transactions'][0];
        }

        return null;
    }
}