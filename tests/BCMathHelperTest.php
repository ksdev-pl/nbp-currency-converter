<?php

namespace Ksdev\NBPCurrencyConverter\Test;

use Ksdev\NBPCurrencyConverter\BCMathHelper;

class BCMathHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testBcround()
    {
        $this->assertEquals('0.5679', BCMathHelper::bcround('0.567891', 4));
        $this->assertEquals('0.5676', BCMathHelper::bcround('0.567591', 4));
        $this->assertEquals('0.5676', BCMathHelper::bcround('0.567559', 4));
        $this->assertEquals('0.5675', BCMathHelper::bcround('0.567549', 4));
    }
}
