<?php

namespace Modules\Base\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Base\Exceptions\ExceptionHandler;
use Modules\Base\Traits\ApiResponse;

class BaseController extends Controller
{
    use ExceptionHandler,ApiResponse;
}
