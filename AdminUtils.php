<?php

namespace GenerateEntity;

class AdminUtils
{
    public static function toCamelCase($word, $lowercasefirst = false)
    {
        $retval = str_replace(' ', '', ucwords(strtr($word, '_-', ' ')));
        if( $lowercasefirst)
        {
            $retval = lcfirst($retval);
        }
        return $retval;
    }

    public static function fromCamelCase($str)
    {
        return preg_replace_callback( '/([A-Z])/', fn($s) => '_' . strtolower($s[1]), $str);
    }
}
