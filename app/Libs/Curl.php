<?php
namespace App\Libs;

class Curl
{
    private $ch         = null;
    private $httpParams = null;
    private $proxy      = null;
    private $timeout    = 5;
    
    public function __construct()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    public function setHeader($header)
    {
        if (is_array($header)) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER  , $header);
        }
        return $this;
    }

    public function setTimeout($time = 5)
    {
        if (5 != $time && $time) {
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $time);    
        }
        return $this;
    }

    public function setProxy($proxy)
    {
        if ($proxy) {
            curl_setopt ($this->ch, CURLOPT_PROXY, $proxy);
        }
        return $this;
    }

    public function setProxyPort($port)
    {
        if (is_int($port)) {
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $port);
        }
        return $this;
    }

    public function setReferer($referer = '')
    {
        if (!empty($referer)) {
            curl_setopt($this->ch, CURLOPT_REFERER , $referer);
        }
        return $this;
    }

    public function setUserAgent($agent = '')
    {
        if (empty($agent)) {
           curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        } else {
            curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
        }
        return $this;
    }

    public function setResponseHeader($show = 0)
    {   
        if (!$show) {
            curl_setopt($this->ch, CURLOPT_HEADER, true);
        } else {
            curl_setopt($this->ch, CURLOPT_HEADER, false);
        }
        return $this;
    }

    public function setParams($params)
    {
        $this->httpParams = $params;
        return $this;
    }

    public function setIp()
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:8.8.8.8', 'CLIENT-IP:8.8.8.8'));
        curl_setopt($this->ch, CURLOPT_REFERER, "http://www.gosoa.com.cn/ ");
        return $this;
    }

    public function get($url)
    {
        if (false !== stripos($url, 'https://')) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if ($this->httpParams && is_array($this->httpParams)) {
            if (false !== strpos($url, '?')) {
                $url .= http_build_query($this->httpParams);
            } else {
                $url .= '?' . http_build_query($this->httpParams);
            }
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER , true);
        $content = curl_exec($this->ch);
        $status  = curl_getinfo($this->ch);
        curl_close($this->ch);
        if (isset($status['http_code']) && 200 == $status['http_code']) {
            return  $content;
        } else {
            return false;
        }
    }

    public function post($url)
    {
        if (stripos($url, 'https://') !== false) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        if ($this->httpParams) {
            if (is_array($this->httpParams)) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->httpParams));
            }
            if (is_string($this->httpParams)) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->httpParams);
            }
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER , true);
        curl_setopt($this->ch, CURLOPT_POST, true);
        $content = curl_exec($this->ch);
        $status  = curl_getinfo($this->ch);
        curl_close($this->ch);
        if (isset($status['http_code']) && 200 == $status['http_code']) {
            return $content;
        } else {
            return false;
        }
    }
}