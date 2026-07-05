<?php

namespace App\Http\Controllers;

use Setting;
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
}
