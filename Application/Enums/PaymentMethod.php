<?php

namespace Application\Enums;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'CREDIT_CARD';
    case PIX = 'PIX';
    case BOLETO = 'BOLETO';
}
