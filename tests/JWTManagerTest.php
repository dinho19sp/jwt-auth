<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean D19sp <dinho19sp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D19sp\JWTAuth\Test\Providers\JWT;

use Mockery;
use D19sp\JWTAuth\Token;
use D19sp\JWTAuth\Payload;
use D19sp\JWTAuth\JWTManager;
use D19sp\JWTAuth\Claims\JwtId;
use D19sp\JWTAuth\Claims\Issuer;
use D19sp\JWTAuth\Claims\Subject;
use D19sp\JWTAuth\Claims\IssuedAt;
use D19sp\JWTAuth\Claims\NotBefore;
use D19sp\JWTAuth\Claims\Expiration;

class JWTManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->jwt = Mockery::mock('D19sp\JWTAuth\Providers\JWT\JWTInterface');
        $this->blacklist = Mockery::mock('D19sp\JWTAuth\Blacklist');
        $this->factory = Mockery::mock('D19sp\JWTAuth\PayloadFactory');
        $this->manager = new JWTManager($this->jwt, $this->blacklist, $this->factory);

        $this->validator = Mockery::mock('D19sp\JWTAuth\Validators\PayloadValidator');
        $this->validator->shouldReceive('setRefreshFlow->check');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    public function it_should_encode_a_payload()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration(123 + 3600),
            new NotBefore(123),
            new IssuedAt(123),
            new JwtId('foo'),
        ];
        $payload = new Payload($claims, $this->validator);

        $this->jwt->shouldReceive('encode')->with($payload->toArray())->andReturn('foo.bar.baz');

        $token = $this->manager->encode($payload);

        $this->assertEquals($token, 'foo.bar.baz');
    }

    /** @test */
    public function it_should_decode_a_token()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration(123 + 3600),
            new NotBefore(123),
            new IssuedAt(123),
            new JwtId('foo'),
        ];
        $payload = new Payload($claims, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());
        $this->factory->shouldReceive('setRefreshFlow->make')->with($payload->toArray())->andReturn($payload);
        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);

        $payload = $this->manager->decode($token);

        $this->assertInstanceOf('D19sp\JWTAuth\Payload', $payload);
    }

    /** @test */
    public function it_should_throw_exception_when_token_is_blacklisted()
    {
        $this->setExpectedException('D19sp\JWTAuth\Exceptions\TokenBlacklistedException');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration(123 + 3600),
            new NotBefore(123),
            new IssuedAt(123),
            new JwtId('foo'),
        ];
        $payload = new Payload($claims, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());
        $this->factory->shouldReceive('setRefreshFlow->make')->with($payload->toArray())->andReturn($payload);
        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(true);

        $this->manager->decode($token);
    }

    /** @test */
    public function it_should_refresh_a_token()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration(123 - 3600),
            new NotBefore(123),
            new IssuedAt(123),
            new JwtId('foo'),
        ];
        $payload = new Payload($claims, $this->validator, true);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());
        $this->jwt->shouldReceive('encode')->with($payload->toArray())->andReturn('baz.bar.foo');

        $this->factory->shouldReceive('setRefreshFlow')->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);
        $this->blacklist->shouldReceive('add')->once()->with($payload);

        $token = $this->manager->refresh($token);

        $this->assertInstanceOf('D19sp\JWTAuth\Token', $token);
        $this->assertEquals('baz.bar.foo', $token);
    }

    /** @test */
    public function it_should_invalidate_a_token()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration(123 + 3600),
            new NotBefore(123),
            new IssuedAt(123),
            new JwtId('foo'),
        ];
        $payload = new Payload($claims, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());
        $this->factory->shouldReceive('setRefreshFlow->make')->with($payload->toArray())->andReturn($payload);
        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);

        $this->blacklist->shouldReceive('add')->with($payload)->andReturn(true);

        $this->manager->invalidate($token);
    }

    /** @test */
    public function it_should_throw_an_exception_when_enable_blacklist_is_set_to_false()
    {
        $this->setExpectedException('D19sp\JWTAuth\Exceptions\JWTException');

        $token = new Token('foo.bar.baz');

        $this->manager->setBlacklistEnabled(false)->invalidate($token);
    }

    /** @test */
    public function it_should_get_the_payload_factory()
    {
        $this->assertInstanceOf('D19sp\JWTAuth\PayloadFactory', $this->manager->getPayloadFactory());
    }

    /** @test */
    public function it_should_get_the_jwt_provider()
    {
        $this->assertInstanceOf('D19sp\JWTAuth\Providers\JWT\JWTInterface', $this->manager->getJWTProvider());
    }

    /** @test */
    public function it_should_get_the_blacklist()
    {
        $this->assertInstanceOf('D19sp\JWTAuth\Blacklist', $this->manager->getBlacklist());
    }
}
