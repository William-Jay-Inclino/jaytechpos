<?php

namespace App;

enum SalesStatus: int
{
    case COMPLETED = 1;
    case REFUNDED = 2;
    case VOIDED = 3;
}
