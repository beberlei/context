<?php
/**
 * Context
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Context;

use Context\Engine;

/**
 * Context Engine Proxy
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Proxy
{
    private $engine;
    private $delegatee;
    private $options;

    public function __construct(Engine $engine, $delegatee, array $options)
    {
        $this->engine    = $engine;
        $this->delegatee = $delegatee;
        $this->options   = $options;
    }

    public function __call($method, $args)
    {
        $options              = $this->options;
        $options['context']   = array($this->delegatee, $method);
        $options['arguments'] = $args;

        return $this->engine->execute($options);
    }
}

