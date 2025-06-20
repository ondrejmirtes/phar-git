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
namespace Hoa\Iterator\Test\Unit;

use Hoa\Iterator as LUT;
use Hoa\Test;
/**
 * Class \Hoa\Iterator\Test\Unit\IteratorIterator.
 *
 * Test suite of the iterator iterator iterator (;-)).
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class IteratorIterator extends Test\Unit\Suite
{
    public function case_inner_iterator()
    {
        $this->given($iterator = new LUT\Map([]), $iteratoriterator = new LUT\IteratorIterator($iterator))->when($result = $iteratoriterator->getInnerIterator())->then->object($result)->isIdenticalTo($iterator);
    }
    public function case_traverse()
    {
        $this->given($iterator = new LUT\Map(['a', 'b', 'c']), $iteratoriterator = new LUT\IteratorIterator($iterator))->when($result = iterator_to_array($iteratoriterator))->then->array($result)->isEqualTo(['a', 'b', 'c']);
    }
    public function case_recursive_leaves_only()
    {
        $this->given($array = ['a' => ['b', 'c', 'd'], 'e' => ['f', 'g', 'i']], $iterator = new LUT\Recursive\Map($array), $iteratoriterator = new LUT\Recursive\Iterator($iterator, LUT\Recursive\Iterator::LEAVES_ONLY))->when($result = iterator_to_array($iteratoriterator, \false))->then->array($result)->isEqualTo(['b', 'c', 'd', 'f', 'g', 'i']);
    }
    public function case_recursive_self_first()
    {
        $this->given($array = ['a' => ['b', 'c', 'd'], 'e' => ['f', 'g', 'i']], $iterator = new LUT\Recursive\Map($array), $iteratoriterator = new LUT\Recursive\Iterator($iterator, LUT\Recursive\Iterator::SELF_FIRST))->when($result = iterator_to_array($iteratoriterator, \false))->then->array($result)->isEqualTo([['b', 'c', 'd'], 'b', 'c', 'd', ['f', 'g', 'i'], 'f', 'g', 'i']);
    }
    public function case_recursive_child_first()
    {
        $this->given($array = ['a' => ['b', 'c', 'd'], 'e' => ['f', 'g', 'i']], $iterator = new LUT\Recursive\Map($array), $iteratoriterator = new LUT\Recursive\Iterator($iterator, LUT\Recursive\Iterator::CHILD_FIRST))->when($result = iterator_to_array($iteratoriterator, \false))->then->array($result)->isEqualTo(['b', 'c', 'd', ['b', 'c', 'd'], 'f', 'g', 'i', ['f', 'g', 'i']]);
    }
}
