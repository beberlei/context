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

namespace Context\Plugins\ExceptionHandler;

use Context\Exception\RuntimeException;

/**
 * Context Exception nests all exceptions that are catched by the generic
 * ExceptionHandler fallback.
 */
class ContextException extends RuntimeException
{
}

