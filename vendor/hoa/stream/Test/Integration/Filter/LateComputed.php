<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
namespace Hoa\Stream\Test\Integration\Filter;

use Hoa\Stream as LUT;
use Hoa\Stream\Filter as SUT;
use Hoa\Test;
/**
 * Class \Hoa\Stream\Test\Integration\Filter\LateComputed.
 *
 * Test suite of the late computed filter class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class LateComputed extends Test\Integration\Suite
{
    public function case_custom_late_computed_filter()
    {
        $this->given($name = 'custom', SUT::register($name, \Hoa\Stream\Test\Integration\Filter\CustomFilter::class), $filename = 'hoa://Test/Vfs/Foo?type=file', $content = 'Hello, World!', file_put_contents($filename, $content), $stream = fopen($filename, 'r'))->when(SUT::append($stream, $name), $result = stream_get_contents($stream))->then->string($result)->isEqualTo(strtolower($content) . ' ' . strlen($content));
    }
}
class CustomFilter extends LUT\Filter\LateComputed
{
    protected function compute()
    {
        $this->_buffer = strtolower($this->_buffer) . ' ' . strlen($this->_buffer);
        // proof that the buffer contains all the data
        return;
    }
}
