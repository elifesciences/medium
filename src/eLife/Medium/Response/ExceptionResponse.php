<?php

namespace eLife\Medium\Response;

class ExceptionResponse
{

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

}
