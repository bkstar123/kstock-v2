<?php
/**
 * AnalysisReportRepository trait
 *
 * Supposed to be used with AnalysisReport model
 *
 * @author: tuanha
 * @date: 09-Aug-2022
 */
namespace App\Models\Behaviors;

use App\ContentObjects\AnalysisReportItem;

trait AnalysisReportRepository
{
    /**
     * Returns all items of the report content
     *
     * @return \Illuminate\Support\Collection || null
     */
    public function getItems()
    {
        $items = collect(json_decode($this->attributes['content'], true));
        if (!empty($items)) {
            $items = $items->map(function ($item) {
                return new AnalysisReportItem(
                    $item['name'],
                    $item['alias'],
                    $item['group'],
                    $item['unit'],
                    $item['description'],
                    $item['values'],
                    $item['alert'] ?? null
                );
            });
            return $items;
        } else {
            return null;
        }
    }

    /**
     * Return the report specific content item
     *
     * @return App\ContentObjects\AnalysisReportItem || null
     */
    public function getItem($itemAlias)
    {
        $items = collect(json_decode($this->attributes['content'], true));
        $item = $items->filter(function ($item) use ($itemAlias) {
            return $item['alias'] == $itemAlias;
        })->first();
        if (!empty($item)) {
            return new AnalysisReportItem(
                $item['name'],
                $item['alias'],
                $item['group'],
                $item['unit'],
                $item['description'],
                $item['values'],
                $item['alert'] ?? null
            );
        } else {
            return null;
        }
    }
}
