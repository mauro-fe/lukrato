<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Controllers\Concerns\HandlesWebPresentation;

abstract class WebController extends BaseController
{
    use HandlesWebPresentation;
}
