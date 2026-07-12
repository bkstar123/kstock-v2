<?php

namespace App\Http\Controllers;

use Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Show all resources
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $graphThemes = array_map(function ($filename) {
            return pathinfo($filename)['filename'];
        }, array_filter(scandir(public_path('js/vendor/highcharts/themes')), function ($filename) {
            return $filename != '.' && $filename != '..';
        }));
        return view('cms.settings.index', compact('graphThemes'));
    }

    /**
     * Update a resource
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'limits' => 'numeric|min:2|max:10'
        ]);
        $settings = $request->except('_token');
        $settings['display_statement_item_code'] = $request->display_statement_item_code ?? 'off';
        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
        flashing('Settings are successfully updated')->success()->flash();
        return back();
    }

    /**
     * Persist the exchange-holiday dates picked in the Refresh calendar.
     * Stored as a JSON array of 'Y-m-d' under the `market_holidays` setting key,
     * consumed by marketHolidays() to exclude those days from data refresh.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateMarketCalendar(Request $request)
    {
        $request->validate([
            'holidays' => 'nullable|string',
        ]);

        $decoded = json_decode($request->input('holidays', '[]'), true);
        $dates = collect(is_array($decoded) ? $decoded : [])
            ->filter(function ($d) {
                return is_string($d)
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
                    && Carbon::hasFormat($d, 'Y-m-d');
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        Setting::set('market_holidays', json_encode($dates));

        flashing('Refresh calendar has been updated')->success()->flash();
        return back();
    }
}
