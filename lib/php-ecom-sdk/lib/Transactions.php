<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\Refund as Refund;
use \StdClass as StdClass;
// ---------------------------------------------------------------------------->
/**
 * Transactions Class
 *
 * @package Due
 */
class Transactions
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
            'id',
            'page'
        );
        foreach ($data as $key) {
            if(!empty($arg_params[$key])){
                $return[$key] = (string) $arg_params[$key];
            }
        }

        return $return;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Transaction Data
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function get(array $arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        $url = '/ecommerce/transactions';
        if(!empty($data['id'])){
            $url .= '/'.$data['id'];
        }else{
            return null;
        }

        //submit to api
        $transactions_data = APIRequests::request(
            $url,
            APIRequests::METHOD_GET
        );

        //return response
        return self::toObj($transactions_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Refund A Card Payment
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function refund($arg_params)
    {
        return Refund::doCardRefund($arg_params);
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Transactions Data
     *
     * @param array $arg_params
     * @return StdClass
     * @throws \Exception
     */
    public static function all(array $arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        $url = '/ecommerce/transactions';
        $filters = '';
        if(!empty($data['page'])&&ctype_digit($data['page'])){
            $filters .= 'page='.$data['page'];
        }
        if(!empty($filters))$filters='?'.$filters;

        //submit to api
        $transactions_data = APIRequests::request(
            $url.$filters,
            APIRequests::METHOD_GET
        );

        //return response
        return self::toListObj($transactions_data['body']);
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
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $transactions_data
     * @return null|object
     */
    public static function toListObj($transactions_data)
    {
        $transactions = array();
        if(!empty($transactions_data['transactions'][0]['id'])){
            foreach ($transactions_data['transactions'] as $transaction) {
                $transactions[] = (object) $transaction;
            }
        }
        $return = new StdClass;
        $return->transactions = $transactions;

        return $return;
    }
}