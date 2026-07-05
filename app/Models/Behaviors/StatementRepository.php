<?php
/**
 * StatementRepository trait
 *
 * Supposed to be used with Statement models
 *
 * @author: tuanha
 * @date: 09-Aug-2022
 */
namespace App\Models\Behaviors;

use App\ContentObjects\StatementItem;

trait StatementRepository
{
    /**
     * Returns all items of the statement content
     *
     * @return \Illuminate\Support\Collection || null
     */
    public function getItems()
    {
        $items = collect(json_decode($this->attributes['content'], true));
        if (!empty($items)) {
            $items = $items->map(function ($item) {
                return new StatementItem(
                    $item['id'],
                    str_pad($item['name'], strlen($item['name']) + ($item['level'] - 1) * 2, '-', STR_PAD_LEFT),
                    $item['parentID'],
                    $item['expanded'],
                    $item['level'],
                    $item['field'],
                    $item['values']
                );
            });
            return $items;
        } else {
            return null;
        }
    }

    /**
     * Return the statement specific content item
     *
     * @return App\ContentObjects\StatementItem || null
     */
    public function getItem($itemID)
    {
        $items = collect(json_decode($this->attributes['content'], true));
        $item = $items->filter(function ($item) use ($itemID) {
            return $item['id'] == $itemID;
        })->first();
        if (!empty($item)) {
            return new StatementItem(
                $item['id'],
                $item['name'],
                $item['parentID'],
                $item['expanded'],
                $item['level'],
                $item['field'],
                $item['values']
            );
        } else {
            return null;
        }
    }
}
