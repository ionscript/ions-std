<?php

namespace Ions\Std\Xml;

use DOMDocument;
use SimpleXMLElement;

/**
 * Class Security
 * @package Ions\Std\Xml
 */
class Security
{
    const ENTITY_DETECT = 'Detected use of ENTITY in XML, disabled to prevent XXE/XEE attacks';

    /**
     * @param $xml
     * @throws \RuntimeException
     */
    protected static function heuristicScan($xml)
    {
        foreach (self::getEntityComparison($xml) as $compare) {
            if (strpos($xml, $compare) !== false) {
                throw new \RuntimeException(self::ENTITY_DETECT);
            }
        }
    }

    /**
     * @param $xml
     * @param DOMDocument|null $dom
     * @return bool|DOMDocument|mixed|SimpleXMLElement
     * @throws \RuntimeException
     */
    public static function scan($xml, DOMDocument $dom = null)
    {
        if (self::isPhpFpm()) {
            self::heuristicScan($xml);
        }

        if (null === $dom) {
            $simpleXml = true;
            $dom = new DOMDocument();
        }

        if (!self::isPhpFpm()) {
            $loadEntities = libxml_disable_entity_loader(true);
            $useInternalXmlErrors = libxml_use_internal_errors(true);
        }

        set_error_handler(function ($errno, $errstr) {
            if (substr_count($errstr, 'DOMDocument::loadXML()') > 0) {
                return true;
            }
            return false;
        }, E_WARNING);
        $result = $dom->loadXml($xml, LIBXML_NONET);
        restore_error_handler();

        if (!$result) {
            // Entity load to previous setting
            if (!self::isPhpFpm()) {
                libxml_disable_entity_loader($loadEntities);
                libxml_use_internal_errors($useInternalXmlErrors);
            }
            return false;
        }

        if (!self::isPhpFpm()) {
            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    if ($child->entities->length > 0) {
                        throw new \RuntimeException(self::ENTITY_DETECT);
                    }
                }
            }
        }

        // Entity load to previous setting
        if (!self::isPhpFpm()) {
            libxml_disable_entity_loader($loadEntities);
            libxml_use_internal_errors($useInternalXmlErrors);
        }

        if (isset($simpleXml)) {
            $result = simplexml_import_dom($dom);
            if (!$result instanceof SimpleXMLElement) {
                return false;
            }
            return $result;
        }
        return $dom;
    }

    /**
     * @param $file
     * @param DOMDocument|null $dom
     * @return bool|DOMDocument|mixed|SimpleXMLElement
     * @throws \InvalidArgumentException
     */
    public static function scanFile($file, DOMDocument $dom = null)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(
                "The file $file specified doesn't exist"
            );
        }
        return self::scan(file_get_contents($file), $dom);
    }

    /**
     * @return bool
     */
    public static function isPhpFpm()
    {
        $isVulnerableVersion = (
            version_compare(PHP_VERSION, '5.5.22', 'lt')
            || (
                version_compare(PHP_VERSION, '5.6', 'gte')
                && version_compare(PHP_VERSION, '5.6.6', 'lt')
            )
        );

        if (substr(php_sapi_name(), 0, 3) === 'fpm' && $isVulnerableVersion) {
            return true;
        }
        return false;
    }

    /**
     * @param $xml
     * @return array
     */
    protected static function getEntityComparison($xml)
    {
        $encodingMap = self::getAsciiEncodingMap();
        return array_map(function ($encoding) use ($encodingMap) {
            $generator   = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encodingMap['UTF-8'];
            return $generator('<!ENTITY');
        }, self::detectXmlEncoding($xml, self::detectStringEncoding($xml)));
    }

    /**
     * @param $xml
     * @return int|string
     */
    protected static function detectStringEncoding($xml)
    {
        return self::detectBom($xml) ?: self::detectXmlStringEncoding($xml);
    }

    /**
     * @param $string
     * @return bool
     */
    protected static function detectBom($string)
    {
        foreach (self::getBomMap() as $criteria) {
            if (0 === strncmp($string, $criteria['bom'], $criteria['length'])) {
                return $criteria['encoding'];
            }
        }
        return false;
    }

    /**
     * @param $xml
     * @return int|string
     */
    protected static function detectXmlStringEncoding($xml)
    {
        foreach (self::getAsciiEncodingMap() as $encoding => $generator) {
            $prefix = $generator('<' . '?xml');
            if (0 === strncmp($xml, $prefix, strlen($prefix))) {
                return $encoding;
            }
        }

        // Fallback
        return 'UTF-8';
    }

    /**
     * @param $xml
     * @param $fileEncoding
     * @return array
     */
    protected static function detectXmlEncoding($xml, $fileEncoding)
    {
        $encodingMap = self::getAsciiEncodingMap();
        $generator   = $encodingMap[$fileEncoding];
        $encAttr     = $generator('encoding="');
        $quote       = $generator('"');
        $close       = $generator('>');

        $closePos    = strpos($xml, $close);
        if (false === $closePos) {
            return array($fileEncoding);
        }

        $encPos = strpos($xml, $encAttr);
        if (false === $encPos
            || $encPos > $closePos
        ) {
            return array($fileEncoding);
        }

        $encPos   += strlen($encAttr);
        $quotePos = strpos($xml, $quote, $encPos);
        if (false === $quotePos) {
            return array($fileEncoding);
        }

        $encoding = self::substr($xml, $encPos, $quotePos);
        return array(
            str_replace('\0', '', $encoding),
            $fileEncoding,
        );
    }

    /**
     * @return array
     */
    protected static function getBomMap()
    {
        return array(
            array(
                'encoding' => 'UTF-32BE',
                'bom'      => pack('CCCC', 0x00, 0x00, 0xfe, 0xff),
                'length'   => 4,
            ),
            array(
                'encoding' => 'UTF-32LE',
                'bom'      => pack('CCCC', 0xff, 0xfe, 0x00, 0x00),
                'length'   => 4,
            ),
            array(
                'encoding' => 'GB-18030',
                'bom'      => pack('CCCC', 0x84, 0x31, 0x95, 0x33),
                'length'   => 4,
            ),
            array(
                'encoding' => 'UTF-16BE',
                'bom'      => pack('CC', 0xfe, 0xff),
                'length'   => 2,
            ),
            array(
                'encoding' => 'UTF-16LE',
                'bom'      => pack('CC', 0xff, 0xfe),
                'length'   => 2,
            ),
            array(
                'encoding' => 'UTF-8',
                'bom'      => pack('CCC', 0xef, 0xbb, 0xbf),
                'length'   => 3,
            ),
        );
    }

    /**
     * @return array
     */
    protected static function getAsciiEncodingMap()
    {
        return array(
            'UTF-32BE'   => function ($ascii) {
                return preg_replace('/(.)/', "\0\0\0\\1", $ascii);
            },
            'UTF-32LE'   => function ($ascii) {
                return preg_replace('/(.)/', "\\1\0\0\0", $ascii);
            },
            'UTF-32odd1' => function ($ascii) {
                return preg_replace('/(.)/', "\0\\1\0\0", $ascii);
            },
            'UTF-32odd2' => function ($ascii) {
                return preg_replace('/(.)/', "\0\0\\1\0", $ascii);
            },
            'UTF-16BE'   => function ($ascii) {
                return preg_replace('/(.)/', "\0\\1", $ascii);
            },
            'UTF-16LE'   => function ($ascii) {
                return preg_replace('/(.)/', "\\1\0", $ascii);
            },
            'UTF-8'      => function ($ascii) {
                return $ascii;
            },
            'GB-18030'   => function ($ascii) {
                return $ascii;
            },
        );
    }

    /**
     * @param $string
     * @param $start
     * @param $end
     * @return string
     */
    protected static function substr($string, $start, $end)
    {
        $substr = '';
        for ($i = $start; $i < $end; ++$i) {
            $substr .= $string[$i];
        }
        return $substr;
    }
}
