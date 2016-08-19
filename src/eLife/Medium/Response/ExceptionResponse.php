<?php

namespace eLife\Medium\Response;

final class ExceptionResponse
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
