<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exchange holidays
    |--------------------------------------------------------------------------
    |
    | Dates ('Y-m-d', Asia/Ho_Chi_Minh) when the Vietnamese exchanges are closed.
    | On these days live market data (price, fundamentals) is cached until the
    | next trading day instead of being re-fetched every few minutes, since the
    | data does not change while the market is closed. Update once a year.
    |
    */

    'holidays' => [
        // '2026-09-02',
    ],

];
