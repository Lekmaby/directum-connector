<?php

namespace Kins\DirectumConnector;

class DirectumService
{
    private $soap;

    public function __construct($uri)
    {
        //todo check connection
    }

    public function get()
    {
        return $this->soap;
    }
}
