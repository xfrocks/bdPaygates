<?php

class bdPaygate_Helper_Resource
{
    public static function isPaid(array $resource)
    {
        return (empty($resource['is_fileless'])
            && empty($resource['download_url'])
            && !empty($resource['price'])
            && !empty($resource['currency'])
        );
    }
}