<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean D19sp <dinho19sp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D19sp\JWTAuth\Test;

use D19sp\JWTAuth\Validators\TokenValidator;

class TokenValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->validator = new TokenValidator();
    }

    /** @test */
    public function it_should_return_true_when_providing_a_well_formed_token()
    {
        $this->assertTrue($this->validator->isValid('one.two.three'));
    }

    /** @test */
    public function it_should_return_false_when_providing_a_malformed_token()
    {
        $this->assertFalse($this->validator->isValid('one.two.three.four.five'));
    }

    /** @test */
    public function it_should_throw_an_axception_when_providing_a_malformed_token()
    {
        $this->setExpectedException('D19sp\JWTAuth\Exceptions\TokenInvalidException');

        $this->validator->check('one.two.three.four.five');
    }
}
