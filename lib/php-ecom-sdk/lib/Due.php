<?php
namespace Due;
// ---------------------------------------------------------------------------->
/**
 * Class Due
 *
 * @package Due
 */
class Due
{
    // ------------------------------------------------------------------------>
    // @var string API key
    public static $apiKey;

    // @var string Payment Rail Type
    public static $railType;

    // @var string Platform Id
    public static $platformId;

    // @var string Application Id
    public static $appId;

    // @var string API base URLs
    public static $domainStage = 'https://stage-api.due.com';
    public static $domainProd = 'https://api.due.com';
    public static $domain = 'https://api.due.com';

    // @var string API version
    public static $apiVersion = 'v1';

    // @var string Environment Name
    public static $envName = 'prod';
    // ------------------------------------------------------------------------>
    /**
     * Get Root API Path
     *
     * @return string
     */
    public static function getRootPath()
    {
        return self::$domain.'/'.self::$apiVersion;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get API key
     *
     * @return string|null
     */
    public static function getApiKey()
    {
        return (empty(self::$apiKey)?'':self::$apiKey);
    }
    // ------------------------------------------------------------------------>
    /**
     * Set API key
     *
     * @param string $arg_api_key
     *
     * @return null
     */
    public static function setApiKey($arg_api_key)
    {
        if(is_string($arg_api_key))self::$apiKey = $arg_api_key;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Payment Rail Type
     *
     * @return string|null
     */
    public static function getRailType()
    {
        return (empty(self::$railType)?'us':self::$railType);
    }
    // ------------------------------------------------------------------------>
    /**
     * Set Payment Rail Type
     *
     * @param string $arg_rail_type
     *
     * @return null
     */
    public static function setRailType($arg_rail_type)
    {
        if(is_string($arg_rail_type))self::$railType = $arg_rail_type;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Environment Name
     *
     * @return string
     */
    public static function getEnvName()
    {
        return self::$envName;
    }
    // ------------------------------------------------------------------------>
    /**
     * Set Environment Name
     *
     * @param string $arg_env_name
     * @return null
     * @throws \Exception
     */
    public static function setEnvName($arg_env_name)
    {
        if($arg_env_name == 'stage'){
            self::$envName = 'stage';
            self::$domain = self::$domainStage;
        }elseif($arg_env_name == 'prod'){
            self::$envName = 'prod';
            self::$domain = self::$domainProd;
        }else{
            throw new \Exception('Invalid Environment Given',4046969);
        }
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Application Id
     *
     * @return string
     */
    public static function getAppId()
    {
        return (empty(self::$appId)?'':self::$appId);
    }
    // ------------------------------------------------------------------------>
    /**
     * Set Application Id
     *
     * @param string $arg_app_id
     *
     * @return null
     */
    public static function setAppId($arg_app_id)
    {
        if(is_string($arg_app_id))self::$appId = $arg_app_id;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get Platform Id
     *
     * @return string
     */
    public static function getPlatformId()
    {
        return (empty(self::$platformId)?'':self::$platformId);
    }
    // ------------------------------------------------------------------------>
    /**
     * Set Platform Id
     *
     * @param string $arg_platform_id
     *
     * @return null
     */
    public static function setPlatformId($arg_platform_id)
    {
        if(is_string($arg_platform_id))self::$platformId = $arg_platform_id;
    }
    // ------------------------------------------------------------------------>
    /**
     * Get API Version
     *
     * @return string
     */
    public static function getApiVersion()
    {
        return self::$apiVersion;
    }
    // ------------------------------------------------------------------------>
    /**
     * Set API Version
     *
     * @param string $arg_api_version
     *
     * @return null
     */
    public static function setApiVersion($arg_api_version)
    {
        if(is_string($arg_api_version))self::$apiVersion = $arg_api_version;
    }
}
