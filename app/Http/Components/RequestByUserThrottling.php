<?php
/**
 * RequestByUserThrottling
 *
 * Do not allow an active user to make multiple attempts against the same request during the given duration
 *
 * @author: tuanha
 * @last-mod: 06-Mar-2021
 */
namespace App\Http\Components;

use Illuminate\Support\Facades\Cache;

trait RequestByUserThrottling
{
    /**
     * Formulate an unique cache key for the active user to the given request
     *
     * @return string
     */
    protected function requestThrottlingKey()
    {
        return request()->method() . '-' . request()->path() . '-' . request()->user()->id;
    }

    /**
     * Verify if the current user is throttled for the request method
     *
     * @return boolean
     */
    protected function isThrottled()
    {
        return Cache::get($this->requestThrottlingKey());
    }

    /**
     * Store the request throttling cache key with value true for a given secends
     *
     * @param $seconds int
     * @return bool
     */
    protected function setRequestThrottling(int $seconds = 10)
    {
        return Cache::put($this->requestThrottlingKey(), true, $seconds);
    }
}
