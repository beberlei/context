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

use Exception;

/**
 * Exception Handler interface
 *
 * Every framework and library should have a corresponding
 * handler that knowns how to handle exceptions.
 */
interface ExceptionHandler
{
    /**
     * Is this handler responsible for exceptions of this kind?
     *
     * @param Exception $e
     * @return bool
     */
    function supports(Exception $e);

    /**
     * Catch the exception and either transform it
     * into a response value of the model context or
     * throw another exception.
     *
     * @param Exception $e
     * @return mixed
     */
    function catchException(Exception $e);
}

