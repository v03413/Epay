<?php
namespace lib\ocr;

use Exception;

class OcrFactory
{
    /**
     * @param string $type
     * @return OcrServiceInterface
     * @throws \InvalidArgumentException
     */
    public static function create($type)
    {
        global $conf;
        switch ($type) {
            case 'aliyun':
                if(empty($conf['ocr_aliyunid']) || empty($conf['ocr_aliyunkey']))throw new Exception('请先配置阿里云OCR文字识别密钥');
                return new AliyunOcrService($conf['ocr_aliyunid'], $conf['ocr_aliyunkey']);
            case 'baidu':
                if(empty($conf['ocr_baiduid']) || empty($conf['ocr_baidukey']))throw new Exception('请先配置百度云OCR文字识别密钥');
                return new BaiduOcrService($conf['ocr_baiduid'], $conf['ocr_baidukey']);
            default:
                throw new \InvalidArgumentException('Unsupported OCR type: ' . $type);
        }
    }
}