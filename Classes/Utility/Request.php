<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Utility;


use Psr\Http\Message\ResponseInterface;

class Request
{

    public static function executePost(ResponseInterface $response, $url, $params)
    {
        $html = '<form data-auto-submit="true" method="POST" action="' . $url . '">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        $html .= '<input type="submit" id="send" value="Send">';
        $html .= '</form>';

        $response->getBody()->write($html);
    }

    public static function executePostCurl($url, $params)
    {
        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params)
        ];
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
    }

}
