<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Loader\ResponseValidator;

use Psr\Http\Message\ResponseInterface;
use Zend\Validator\ValidatorInterface;

class StatusOk implements ValidatorInterface
{
    protected $message = null;

    public function isValid($value)
    {
        if (!$value instanceof ResponseInterface) {
            $this->message = "Value is not implement " . ResponseInterface::class;
            return false;
        }

        if ($value->getStatusCode() != 200) {
            $this->message = "Reason phrase is {$value->getReasonPhrase()}";
            return false;
        }

        return true;
    }

    public function getMessages()
    {
        return $this->message;
    }
}
