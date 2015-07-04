<?php

namespace Ksdev\NBPCurrencyConverter;

class BCMathHelper
{
    /**
     * Round the arbitrary precision number
     *
     * @see http://php.net/manual/en/function.bcscale.php#79628
     *
     * @param string $number
     * @param int $scale
     *
     * @return string
     */
    public static function bcround($number, $scale = 0)
    {
        $fix = '5';
        for ($i = 0; $i < $scale; $i++) {
            $fix = "0{$fix}";
        }
        $number = bcadd($number, "0.{$fix}", $scale + 1);
        return bcdiv($number, '1.0', $scale);
    }
}
