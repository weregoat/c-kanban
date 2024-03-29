<?php
/**
 * Created by IntelliJ IDEA.
 * User: reroc
 * Date: 3/5/19
 * Time: 2:07 PM
 */

use KanbanBoard\Utilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{

    /**
     * @dataProvider getEnvironments
     */
    public function testEnv($name, $value, $default, $expected)
    {
        if ($name == 'GH_TEST') {
            putenv($name . "=" . $value);
        }
        if ($expected === NULL) {
            $this->expectException(RuntimeException::class);
        }
        $result = Utilities::env('GH_TEST', $default);
        $this->assertEquals($expected, $result);
        putenv($name);
    }

    /**
     * @dataProvider getValues
     */
    public function testGetValue($array, $key, $expected, $exception)
    {
        if ($exception == TRUE) {
            $this->expectException($expected);
        }
        $result = Utilities::getValue($array, $key);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getValues
     */
    public function testHasValue($array, $key, $expected, $exception)
    {
        if ($exception == TRUE) {
            $this->expectException($expected);
        }
        $result = Utilities::hasValue($array, $key);
        if (empty($expected)) {
            $this->assertFalse($result);
        } else {
            $this->assertTrue($result);
        }
    }

    /**
     * @dataProvider getArrayValues
     */
    public function testGetArrayValue($array, $key, $expected, $exception)
    {
        if ($exception == TRUE) {
            $this->expectException($expected);
        }
        $result = Utilities::getArrayValue($array, $key);
        $this->assertEquals($expected, $result);
    }



    public function getValues() {
        return [
            [['foo' => 'bar'], 'foo', 'bar', false],
            [['foo' => ''], 'foo', '', false],
            [['foo' => 'bar'], 'fooo', NULL, false],
            ['not_array', 'foo', 'TypeError', true]
        ];
    }

    public function getArrayValues() {
        return [
            [['foo' => 'bar'], 'foo', [], false],
            [['foo' => ['foobar' => 'barbar']], 'foo', ['foobar' => 'barbar'], false],
            [['foo' => ['foobar' => 'barbar']], 'fooo', [], false],
            ['not_array', 'foo', 'TypeError', true]
        ];
    }

    public function getEnvironments() {
        return [
            ['GH_TEST', 'test', '', 'test'],
            ['GH_TEST', 'test', 'foo', 'test'],
            ['FAILURE', 'failure', 'foo', 'foo'],
            ['FAILURE', 'failure', NULL, NULL],
            ['GH_TEST', '', NULL, NULL],
            ['GH_TEST', '', 'bar', 'bar'],
        ];
    }
}
