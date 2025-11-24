<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    // Intentionally minimal — extending Laravel's base controller so middleware(),
    // dispatching, and other controller helpers are available to application controllers.
}
