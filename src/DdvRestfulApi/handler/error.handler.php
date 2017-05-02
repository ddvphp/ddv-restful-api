<?php
/**
 * Error Handler
 *
 * This is the custom error handler that is declared at the (relative)
 * top of CodeIgniter.php. The main reason we use this is to permit
 * PHP errors to be logged in our own log files since the user may
 * not have access to server logs. Since this function effectively
 * intercepts PHP errors, however, we also need to display errors
 * based on the current error_reporting level.
 * We do that with the use of a PHP error template.
 *
 * @param int $severity
 * @param string  $message
 * @param string  $filepath
 * @param int $line
 * @return  void
 */

if ( ! function_exists('_error_handler'))
{
  function _error_handler($severity, $message, $filepath, $line = 0, $errcontext=null)
  {
    return DdvPhp\DdvRestfulApi\Exception\Handler::errorHandler($severity, $message, $filepath, $line, $errcontext);
  }
}
if ( ! function_exists('error_handler'))
{
  function error_handler($severity, $message, $filepath, $line = 0, $errcontext=null)
  {
    return _error_handler($severity, $message, $filepath, $line, $errcontext);
  }
}
?>