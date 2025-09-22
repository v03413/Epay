<?php
namespace lib\ocr;

interface OcrServiceInterface
{
    function idcard($file_path);

    function idcard_back($file_path);

    function passport($file_path);

    function bank_card($file_path);

    function business_license($file_path);

    function bank_account_license($file_path);
}