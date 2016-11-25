<?php
namespace Neos\Flow\Tests\Unit\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Cryptography\Algorithms;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the cryptographic algorithms
 */
class AlgorithmsTest extends UnitTestCase
{
    /**
     * Data provider for pbkdf2TestVectorsAreCorrect()
     *
     * Based on certified test vectors from http://tools.ietf.org/html/rfc6070
     *
     * @return array
     */
    public function pbkdf2TestVectors()
    {
        $output = [];

        $output[] = ['password', 'salt', 1, 20, '0c60c80f961f0e71f3a9b524af6012062fe037a6'];
        $output[] = ['password', 'salt', 2, 20, 'ea6c014dc72d6f8ccd1ed92ace1d41f0d8de8957'];
        $output[] = ['password', 'salt', 4096, 20, '4b007901b765489abead49d926f721d065a429c1'];
        $output[] = ['passwordPASSWORDpassword', 'saltSALTsaltSALTsaltSALTsaltSALTsalt', 4096, 25, '3d2eec4fe41c849b80c8d83662c0e44a8b291a964cf2f07038'];
        $output[] = ['pass' . pack('H', '00') . 'word', 'sa' . pack('H', '00') . 'lt', 4096, 16, '56fa6aa75548099dcc37d7f03425e0c3'];

        return $output;
    }

    /**
     * @test
     * @dataProvider pbkdf2TestVectors
     */
    public function pbkdf2TestVectorsAreCorrect($password, $salt, $iterationCount, $derivedKeyLength, $output)
    {
        $result = Algorithms::pbkdf2($password, $salt, $iterationCount, $derivedKeyLength, 'sha1');
        $this->assertEquals(unpack('H*', pack('H*', $output)), unpack('H*', $result));
    }
}
