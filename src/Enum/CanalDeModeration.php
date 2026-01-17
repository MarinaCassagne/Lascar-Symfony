<?php

namespace App\Enum;

enum CanalDeModeration: string 
{
    case WEB = "web";//Rajout
    case MOBILE = "mobile";//Rajout
    case Email = 'Email';
    case Push = 'Push';
}