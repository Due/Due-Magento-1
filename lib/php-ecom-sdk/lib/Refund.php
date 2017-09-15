<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\APIRequests as APIRequests;
// ---------------------------------------------------------------------------->
/**
 * Refund Class
 *
 * @package Due
 */
class Refund
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
            'transaction_id',
            'customer_id',
            'security_token',
            'customer_ip'
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
     * Refund A Card Payment
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function doCardRefund($arg_params)
    {
        //validate params
        $data = array();
        if(is_array($arg_params)){
            $data = self::getParams($arg_params);
        }else{
            $transaction = json_decode(json_encode($arg_params), true);
            if(!empty($transaction['id'])){
                $data = array('transaction_id'=>$transaction['id']);
            }
        }
        //submit to api
        $refund_data = APIRequests::request(
            '/ecommerce/refund/card',
            APIRequests::METHOD_POST,
            array('payload'=>$data)
        );

        //return response
        return self::toObj($refund_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $transactions_data
     * @return null|object
     */
    public static function toObj($payment_data)
    {
        if(!empty($payment_data['transactions'][0]['id'])){
            return (object) $payment_data['transactions'][0];
        }

        return null;
    }
}