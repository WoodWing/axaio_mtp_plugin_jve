<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Math;

use Zend\Math;
use Zend\Math\Rand;

/**
 * @group      Zend_Math
 */
class RandTest extends \PHPUnit_Framework_TestCase
{
    public static function provideRandInt()
    {
        return array(
            array(2, 1, 10000, 100, 0.9, 1.1, false),
            array(2, 1, 10000, 100, 0.8, 1.2, true)
        );
    }

    public function testRandBytes()
    {
        for ($length = 1; $length < 4096; $length++) {
            $rand = Rand::getBytes($length);
            $this->assertNotFalse($rand);
            $this->assertEquals($length, strlen($rand));
        }
    }

    public function testRandBoolean()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getBoolean();
            $this->assertInternalType('bool', $rand);
        }
    }

    /**
     * @dataProvider dataProviderForTestRandIntegerRangeTest
     */
    public function testRandIntegerRangeTest($min, $max, $cycles)
    {
        $counter = array();
        for ($i = $min; $i <= $max; $i++) {
            $counter[$i] = 0;
        }

        for ($j = 0; $j < $cycles; $j++) {
            $value = Rand::getInteger($min, $max);
            $this->assertInternalType('integer', $value);
            $this->assertGreaterThanOrEqual($min, $value);
            $this->assertLessThanOrEqual($max, $value);
            $counter[$value]++;
        }

        foreach ($counter as $value => $count) {
            $this->assertGreaterThan(0, $count, sprintf('The bucket for value %d is empty.', $value));
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTestRandIntegerRangeTest()
    {
        return array(
            array(0, 100, 10000),
            array(-100, 100, 10000),
            array(-100, 50, 10000),
            array(0, 63, 10000),
            array(0, 64, 10000),
            array(0, 65, 10000),
        );
    }

    /**
     * A Monte Carlo test that generates $cycles numbers from 0 to $tot
     * and test if the numbers are above or below the line y=x with a
     * frequency range of [$min, $max]
     *
     * Note: this code is inspired by the random number generator test
     * included in the PHP-CryptLib project of Anthony Ferrara
     * @see https://github.com/ircmaxell/PHP-CryptLib
     *
     * @dataProvider provideRandInt
     */
    public function testRandInteger($num, $valid, $cycles, $tot, $min, $max, $strong)
    {
        try {
            $test = Rand::getBytes(1, $strong);
        } catch (\Zend\Math\Exception\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $i     = 0;
        $count = 0;
        do {
            $up   = 0;
            $down = 0;
            for ($i = 0; $i < $cycles; $i++) {
                $x = Rand::getInteger(0, $tot, $strong);
                $y = Rand::getInteger(0, $tot, $strong);
                if ($x > $y) {
                    $up++;
                } elseif ($x < $y) {
                    $down++;
                }
            }
            $this->assertGreaterThan(0, $up);
            $this->assertGreaterThan(0, $down);
            $ratio = $up / $down;
            if ($ratio > $min && $ratio < $max) {
                $count++;
            }
            $i++;
        } while ($i < $num && $count < $valid);

        if ($count < $valid) {
            $this->fail('The random number generator failed the Monte Carlo test');
        }
    }

    public function testIntegerRangeFail()
    {
        $this->setExpectedException(
            'Zend\Math\Exception\DomainException',
            'min parameter must be lower than max parameter'
        );
        $rand = Rand::getInteger(100, 0);
    }

    public function testIntegerRangeOverflow()
    {
        $values = 0;
        $cycles = 100;
        for ($i = 0; $i < $cycles; $i++) {
            $values += Rand::getInteger(0, PHP_INT_MAX);
        }

        // It's not possible to test $values > 0 because $values may suffer a integer overflow
        $this->assertNotEquals(0, $values);
    }

    public function testRandFloat()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getFloat();
            $this->assertInternalType('float', $rand);
            $this->assertGreaterThanOrEqual(0, $rand);
            $this->assertLessThanOrEqual(1, $rand);
        }
    }

    public function testGetString()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getString($length, '0123456789abcdef');
            $this->assertEquals(strlen($rand), $length);
            $this->assertEquals(1, preg_match('#^[0-9a-f]+$#', $rand));
        }
    }

    public function testGetStringBase64()
    {
        for ($length = 1; $length < 512; $length++) {
            $rand = Rand::getString($length);
            $this->assertEquals(strlen($rand), $length);
            $this->assertEquals(1, preg_match('#^[0-9a-zA-Z+/]+$#', $rand));
        }
    }

    public function testHashTimingSourceStrengthIsVeryLow()
    {
        $this->assertEquals(1, (string) Math\Source\HashTiming::getStrength());
    }

    public function testHashTimingSourceStrengthIsRandomWithCorrectLength()
    {
        $source = new Math\Source\HashTiming;
        $rand = $source->generate(32);
        $this->assertEquals(32, strlen($rand));
        $rand2 = $source->generate(32);
        $this->assertNotEquals($rand, $rand2);
    }

    public function testAltGeneratorIsRandomWithCorrectLength()
    {
        $source = Math\Rand::getAlternativeGenerator();
        $rand = $source->generate(32);
        $this->assertEquals(32, strlen($rand));
        $rand2 = $source->generate(32);
        $this->assertNotEquals($rand, $rand2);
    }
}
