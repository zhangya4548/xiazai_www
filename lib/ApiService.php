<?php
namespace Lib;

use Curl\Curl;

/**
 * Class ApiService
 * @package Lib
 */
class ApiService
{
    public $log;
    public  function __construct()
    {
        $this->log = new LogService();
    }

    /**
     * @param        $url
     * @param string $method
     * @param array  $params
     * @param array  $headers
     * @return array
     * @throws \ErrorException
     */
    public  function curl($url, $method = 'get', $params = [], $headers = [])
    {
        $curl = new Curl();

        //设置头信息
        foreach ($headers as $k => $v)
        {
            $curl->setHeader($k, $v);
        }

        switch ($method)
        {
            case 'get':
                $curl->get($url, $params);
                break;
            case 'post':
                $curl->post($url, $params);
                break;
            case 'put':
                $curl->put($url, $params);
                break;
            case 'delete':
                $curl->delete($url, $params);
                break;
            default: throw new \Exception('需要指定请求方法'); break;

        }

        //var_dump($res); //打印请求头
        // var_dump($curl->httpError); //打印请求头
        //var_dump($curl->requestHeaders); //打印请求头
        //var_dump($curl->responseHeaders);//打印返回头
        $return               = [];
        $return['statusCode'] = $curl->httpStatusCode ?: 503;
        $return['reHeader']   = $curl->requestHeaders ?: '';
        $return['rawHeader']  = $curl->responseHeaders ?: '';
        $return['rawBody']    = $curl->rawResponse;
        $return['body']       = json_decode($curl->rawResponse, true) ?: [];

        if ($curl->error)
        {
            $return['rawBody']    = $curl->errorMessage;
            $return['body']       = $curl->errorMessage;
            $this->log->warning('CURL 请求异常! URL: ' . $url . ' 请求参数: ' . json_encode($params) . ' 错误码: ' . $curl->errorCode . ' 错误内容: ' . $curl->errorMessage);
        }

        // var_dump($return);die;
        return $return;
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $headers
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public  function request($method, $url, $headers = [], $params = [])
    {
        $response = $this->curl($url, $method, $params, $headers);

        $data = [
            'statusCode' => $response['statusCode'] ?? 0,
            'data'       => $response['body'] ?? '',
            'raw'        => $response['rawBody'] ?? '',
        ];

        return $data;
    }


}
