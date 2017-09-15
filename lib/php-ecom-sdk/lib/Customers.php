<?php
namespace Due;
// ---------------------------------------------------------------------------->
use \Due\APIRequests as APIRequests;
use \StdClass as StdClass;
// ---------------------------------------------------------------------------->
/**
 * Customers Class
 *
 * @package Due
 */
class Customers
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
            'email',
            'full_name',
            'phone',
            'card_id',
            'card_hash',
            'page'
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
     * Get Customer Data
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function get(array $arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        $url = '/ecommerce/customers';
        if(!empty($data['id'])){
            $url .= '/'.$data['id'];
        }else{
            return null;
        }

        //submit to api
        $customer_data = APIRequests::request(
            $url,
            APIRequests::METHOD_GET
        );

        //return response
        return self::toObj($customer_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Customer Data
     *
     * @param array $arg_params
     * @return StdClass
     * @throws \Exception
     */
    public static function all(array $arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        $url = '/ecommerce/customers';
        $filters = '';
        if(!empty($data['page'])&&ctype_digit($data['page'])){
            $filters .= 'page='.$data['page'];
        }
        if(!empty($filters))$filters='?'.$filters;

        //submit to api
        $customer_data = APIRequests::request(
            $url.$filters,
            APIRequests::METHOD_GET
        );

        //return response
        return self::toListObj($customer_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Create A Customer
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function create(array $arg_params)
    {
        //validate params
        $data = self::getParams($arg_params);
        if(!empty($data['metadata'])){
            $data['metadata'] = json_encode($data['metadata']);
        }

        //submit to api
        $customer_data = APIRequests::request(
            '/ecommerce/customers',
            APIRequests::METHOD_POST,
            $data
        );

        //return response
        return self::toObj($customer_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Update A Customer
     *
     * @param array|object $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function update($arg_params)
    {
        //validate params
        if(is_array($arg_params)){
            $data = self::getParams($arg_params);
        }else{
            $data = self::getParams(json_decode(json_encode($arg_params), true));
        }
        $url = '/ecommerce/customers';
        if(!empty($data['id'])){
            $url .= '/'.$data['id'];
        }else{
            return null;
        }
        unset($data['id']);

        //submit to api
        $customer_data = APIRequests::request(
            $url,
            APIRequests::METHOD_PUT,
            $data
        );

        //return response
        return self::toObj($customer_data['body']);
    }
    // ------------------------------------------------------------------------>
    /**
     * Charge A Card
     *
     * @param array $arg_params
     * @return null|object
     * @throws \Exception
     */
    public static function charge($arg_params)
    {
        return Charge::doCardPayment($arg_params);
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $customer_data
     * @return null|object
     */
    public static function toObj($customer_data)
    {
        if(!empty($customer_data['customers'][0]['id'])){
            return (object) $customer_data['customers'][0];
        }

        return null;
    }
    // ------------------------------------------------------------------------>
    /**
     * Convert array to object
     *
     * @param $customers_data
     * @return null|object
     */
    public static function toListObj($customers_data)
    {
        $customers = array();
        if(!empty($customers_data['customers'][0]['id'])){
            foreach ($customers_data['customers'] as $customer) {
                $customers[] = (object) $customer;
            }
        }
        $return = new StdClass;
        $return->customers = $customers;

        return $return;
    }
}