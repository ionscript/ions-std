<?php

namespace Ions\Std\Net;

/**
 * Class RemoteAddress
 * @package Ions\Std\Net
 */
class RemoteAddress
{
    /**
     * @var bool
     */
    protected $useProxy = false;
    /**
     * @var array
     */
    protected $trustedProxies = [];
    /**
     * @var string
     */
    protected $proxyHeader = 'HTTP_X_FORWARDED_FOR';

    /**
     * @param bool $useProxy
     * @return $this
     */
    public function setUseProxy($useProxy = true)
    {
        $this->useProxy = $useProxy;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseProxy()
    {
        return $this->useProxy;
    }

    /**
     * @param array $trustedProxies
     * @return $this
     */
    public function setTrustedProxies(array $trustedProxies)
    {
        $this->trustedProxies = $trustedProxies;
        return $this;
    }

    /**
     * @param string $header
     * @return $this
     */
    public function setProxyHeader($header = 'X-Forwarded-For')
    {
        $this->proxyHeader = $this->normalizeProxyHeader($header);
        return $this;
    }

    /**
     * @return bool|mixed|string
     */
    public function getIpAddress()
    {
        $ip = $this->getIpAddressFromProxy();

        if ($ip) {
            return $ip;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * @return bool|mixed
     */
    protected function getIpAddressFromProxy()
    {
        if (!$this->useProxy || (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $this->trustedProxies, true))) {
            return false;
        }

        $header = $this->proxyHeader;

        if (!isset($_SERVER[$header]) || empty($_SERVER[$header])) {
            return false;
        }

        $ips = explode(',', $_SERVER[$header]);

        $ips = array_map('trim', $ips);

        $ips = array_diff($ips, $this->trustedProxies);

        if (empty($ips)) {
            return false;
        }

        return array_pop($ips);
    }

    /**
     * @param $header
     * @return mixed|string
     */
    protected function normalizeProxyHeader($header)
    {
        $header = strtoupper($header);

        $header = str_replace('-', '_', $header);

        if (0 !== strpos($header, 'HTTP_')) {
            $header = 'HTTP_' . $header;
        }

        return $header;
    }
}
