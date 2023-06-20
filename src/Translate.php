<?php

namespace TONYLABS\Translate;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Translate
{
    protected string $app_id;
    protected string $app_secret;
    protected string $from_locale;
    protected string $to_locale;
    protected string $base_url = 'https://openapi.youdao.com/api';
    protected string $sign_type = 'v3';

    public function __construct(string $app_id, string $app_secret, string $from_locale, string $to_locale)
    {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->from_locale = $from_locale;
        $this->to_locale = $to_locale;
    }

    public function translate(string $words)
    {
        $salt = (string) Str::uuid();
        $timestamp = time();
        $signature = $this->app_id . $this->truncate($words) . $salt . $timestamp . $this->app_secret;

        $arrayData = [
            'q' => $words,
            'appKey' => $this->app_id,
            'salt' => $salt
        ];
        $arrayData['from'] = $this->from_locale;
        $arrayData['to'] = $this->to_locale;
        $arrayData['signType'] = $this->sign_type;
        $arrayData['curtime'] = $timestamp;
        $arrayData['sign'] = hash('sha256', $signature);

        $objResponse = Http::asForm()->post($this->base_url, $arrayData);

        if ($objResponse->successful() && !$objResponse->object()->errorCode)
        {
            return Arr::first($objResponse->object()->translation);
        }
        else
        {
            return false;
        }
    }

    private function abslength($string)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($string, 'utf-8');
        }
        preg_match_all('/./u', $string, $matches);
        return count($matches[0]);
    }

    private function truncate($words)
    {
        $length = $this->abslength($words);
        return $length <= 20 ? $words : (mb_substr($words, 0, 10) . $length . mb_substr($words, $length - 10, $length));
    }
}
