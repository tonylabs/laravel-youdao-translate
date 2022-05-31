<?php

namespace TONYLABS\Translate;

use Illuminate\Support\Facades\Http;

class Translate
{
    protected bool $debug;
    protected string $app_id;
    protected string $app_secret;
    protected string $from_locale;
    protected string $to_locale;
    protected string $base_url = 'https://openapi.youdao.com/api';
    protected string $sign_type = 'V3';

    public function __construct(string $app_id, string $app_secret, string $from_locale, string $to_locale, bool $debug = false)
    {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->from_locale = $from_locale;
        $this->to_locale = $to_locale;
        $this->debug = $debug;
    }

    public function translate(string $words)
    {
        $salt = $this->uuid();
        $timestamp = time();
        $signature = $this->app_id . $this->truncate($words) . $salt . $timestamp . $this->app_secret;

        if ($this->debug) ray($signature);

        $arrayData = [
            'q' => $words,
            'appKey' => $this->app_id,
            'salt' => $salt,
        ];
        $arrayData['from'] = $this->from_locale;
        $arrayData['to'] = $this->to_locale;
        $arrayData['signType'] = $this->sign_type;
        $arrayData['curtime'] = $timestamp;
        $arrayData['sign'] = hash('sha256', $signature);
        $arrayData['vocabId'] = '';

        if ($this->debug) ray($arrayData);

        return Http::acceptJson()->post($this->base_url, $arrayData)->json();
    }

    private function uuid()
    {
        $micro_time = microtime(); //@return: 0.34063300 1653996399
        list($microseconds, $timestamp) = explode(' ', $micro_time);
        $microseconds_hex = dechex($microseconds * 1000000);
        $timestamp_hex = dechex($timestamp);
        $this->fix_length($microseconds_hex, 5);
        $this->fix_length($timestamp_hex, 6);
        $uuid = $microseconds_hex;
        $uuid .= $this->uuid_section(3);
        $uuid .= '-';
        $uuid .= $this->uuid_section(4);
        $uuid .= '-';
        $uuid .= $this->uuid_section(4);
        $uuid .= '-';
        $uuid .= $this->uuid_section(4);
        $uuid .= '-';
        $uuid .= $timestamp_hex;
        $uuid .= $this->uuid_section(6);
        return $uuid;
    }

    private function uuid_section($limit)
    {
        $uuid_section = '';
        for ($i = 0; $i < $limit; $i++) {
            $uuid_section .= dechex(mt_rand(0, 15));
        }
        return $uuid_section;
    }

    private function abslength($str)
    {
        if (empty($str)) return 0;
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    private function truncate($words)
    {
        $length = $this->abslength($words);
        return $length <= 20 ? $words : (mb_substr($words, 0, 10) . $length . mb_substr($words, $length - 10, $length));
    }

    private function fix_length(&$string, $length)
    {
        $string_length = strlen($string);
        if ($string_length < $length) {
            $string = str_pad($string, $length, '0');
        } else if ($string_length > $length) {
            $string = substr($string, 0, $length);
        }
    }
}
