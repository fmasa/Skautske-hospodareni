<?php

declare(strict_types=1);

namespace App\AccountancyModule\Helpers;

use JShrink\Minifier;
use WebLoader\Compiler;

class JSMinificationFilter
{
    public function __invoke(string $code, Compiler $compiler) : string
    {
        return (string) Minifier::minify($code, ['flaggedComments' => false]);
    }
}
