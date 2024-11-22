<?php

namespace App\Entity;

enum HttpMethods:string
{
    case POST = 'POST';
    case GET = 'GET';
}
