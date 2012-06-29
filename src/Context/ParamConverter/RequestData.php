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

namespace Context\ParamConverter;

class RequestData
{
    private $parameters = array();

    private $rawInput;

    public function __construct(array $parameters = array(), $rawInput = null)
    {
        $this->parameters = $parameters;
        $this->rawInput   = $rawInput;
    }

    public function has($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    public function get($name)
    {
        return $this->parameters[$name];
    }

    public function hasRawInput()
    {
        return ! empty($this->rawInput);
    }

    public function getRawInput()
    {
        return $this->rawInput;
    }
}

