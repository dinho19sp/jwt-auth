<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean D19sp <dinho19sp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D19sp\JWTAuth\Claims;

class Subject extends Claim
{
    /**
     * The claim name.
     *
     * @var string
     */
    protected $name = 'sub';
}
