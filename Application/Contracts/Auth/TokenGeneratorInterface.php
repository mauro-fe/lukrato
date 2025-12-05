<?php

namespace Application\Contracts\Auth;

interface TokenGeneratorInterface
{
    public function generate(): string;
}