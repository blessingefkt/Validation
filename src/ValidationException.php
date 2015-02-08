<?php namespace Iyoworks\Validation;

use Exception;
use Illuminate\Contracts\Support\MessageBag;

class ValidationException extends \RuntimeException
{
    /**
     * @var MessageBag
     */
    private $bag;
    private $htmlMessage;

    public function __construct(MessageBag $bag, $message = "", $code = 0, Exception $previous = null)
    {
        $this->bag = $bag;

        if (!$bag->isEmpty()) {
            if (!empty($message))
                $message = implode('. ', $message->all());
        }

        parent::__construct($message, $code ?: 403, $previous);
    }

    /**
     * @return MessageBag
     */
    public function getBag()
    {
        return $this->bag;
    }

    /**
     * @return string
     */
    public function getHtmlMessage()
    {
        if (!$this->htmlMessage)
            $this->htmlMessage = sprintf("<ul class='validation-msgs'><li>%s</li></ul>",
                join('</li><li>', $this->bag->all()));
        return $this->htmlMessage;
    }

}