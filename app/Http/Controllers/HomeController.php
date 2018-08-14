<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Brag;
use Intervention\Image\ImageManagerStatic as Image;

class HomeController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth')->except(['index']);
    }

    public function index()
    {
        $brag = Brag::inRandomOrder()->first();

        // dd($brag->description);
        return view('index', compact('brag'));
    }

    public function submit()
    {
        $brag = Brag::where('id', request('id'))->first();
        $customText = request()->comment;

        // This is the image template
        $img = Image::make('images/humblebrag.jpg');

        $img->text($brag->description, 20, 20, function($font) {
            $font->file(public_path('font.ttf'));
            $font->size(32);
            $font->color('#fdf6e3');
            $font->align('left');
            $font->valign('top');
            $font->angle(0);
        });

        $img->text($customText, 20, 350, function($font) {
            $font->file(public_path('font.ttf'));
            $font->size(32);
            $font->color('#fdf6e3');
            $font->align('left');
            $font->valign('top');
            $font->angle(0);
        });

        // Todo: image filename should be unique and only stored temporarily (user should be able to upload/share to Twitter/Facebook)
        $img->save('images/brags/test.jpg');

        return view('edit', ['brag' => $brag, 'customText' => $customText]);
    }

    public function edit()
    {
        // $brag = Brag::where('id', request()->id)->get();
        // $brag = Brag::where('id', 1)->get();
        $customText = request()->comment;

        dd($request()->id);
        return view('edit', ['brag' => $brag, 'customText' => $customText]);
    }




}
