<?php

namespace App\Widgets;

use App\Brag;
use TCG\Voyager\Widgets\BaseDimmer;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Brags extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = \App\Brag::count();
        $string = trans_choice('Brags', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-rocket',
            'title'  => "{$count} {$string}",
            'text'   => "You have {$count} brags in your database. Click on button below to view all brags.",
            'button' => [
                'text' => 'View all brags',
                'link' => route('voyager.brags.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        // return Auth::user()->can('browse', Voyager::model('Brag'));
        // dd(Voyager::model('Page'));
        // dd(\App\Brag::class);
        // return Auth::user()->can('browse', App\Brag::class);
        return true;
    }
}
