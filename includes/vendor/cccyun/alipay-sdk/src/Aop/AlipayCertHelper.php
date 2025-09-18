<?php

namespace Alipay\Aop;

class AlipayCertHelper
{

	/**
	 * 从证书中提取序列号
	 * @param string $certPath 证书路径
	 * @return string
	 */
    public static function getCertSN(string $certPath): string
    {
        $cert = file_get_contents($certPath);
        $cert = str_replace("\n\n", "\n", $cert);
        $ssl = openssl_x509_parse($cert);
	    return md5(self::array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
    }

    /**
     * 数组转字符串
     * @param array $array 数组
     * @return string
     */
    private static function array2string(array $array): string
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }

    /**
     * 提取根证书序列号
     * @param string $certPath 根证书
     * @return string|null
     */
    public static function getRootCertSN(string $certPath): ?string
    {
        $cert = file_get_contents($certPath);
        $array = explode("-----END CERTIFICATE-----", $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if(strpos($ssl[$i]['serialNumber'],'0x') === 0){
                $ssl[$i]['serialNumber'] = self::hex2dec($ssl[$i]['serialNumberHex']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5(self::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {
                    $SN = $SN . "_" . md5(self::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    /**
     * 0x转高精度数字
     * @param $hex
     * @return int|string
     */
    private static function hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

}