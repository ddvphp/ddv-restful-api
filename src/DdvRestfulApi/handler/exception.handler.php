<?php
/**
 * Exception Handler
 *
 * Sends uncaught exceptions to the logger and displays them
 * only if display_errors is On so that they don't show up in
 * production environments.
 *
 * @param Exception $exception
 * @return  void
 */

if ( ! function_exists('_exception_handler'))
{
  function _exception_handler($exception)
  {
    return DdvPhp\DdvRestfulApi\Exception\Handler::exceptionHandler($exception);
  }
}
if ( ! function_exists('exception_handler'))
{
  function exception_handler($exception)
  {
    return _exception_handler($exception);
  }
}
?>