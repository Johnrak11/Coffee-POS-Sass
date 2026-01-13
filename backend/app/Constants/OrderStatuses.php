<?php

namespace App\Constants;

class OrderStatuses
{
    const STATUS_QUEUE = 'queue';
    const STATUS_PREPARING = 'preparing';
    const STATUS_SERVED = 'served';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
}
