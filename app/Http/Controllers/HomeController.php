<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Brag;
use Intervention\Image\Imagick\Font;
use Intervention\Image\ImageManager;
use SammyK\LaravelFacebookSdk\LaravelFacebookSdk as Facebook;
use Illuminate\Contracts\Session\Session;
use Thujohn\Twitter\Twitter;

class HomeController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth')->except(['index']);
    }

    public function index()
    {
        $brags = Brag::all();
        $brag = $brags->random();

        // dd($brag->description);
        return view('index', compact('brag'));
    }

    public function submit(ImageManager $imageManager)
    {
        $brag = Brag::where('id', request('id'))->first();
        $customText = request()->comment;
        
        // Delete extra lines
        $lines      = preg_split('/\r?\n/', trim($customText));
        array_splice($lines, 4);
        $customText = implode("\n", $lines);
        
        // Get background image
        $backgroundImage        = $imageManager->make('images/humblebrag.jpg');
        
        $bgWidth                = $backgroundImage->width();
        $bgHeight               = $backgroundImage->height();
        
        $fontFile               = public_path('font.ttf');
        
        // Reserve space on bottom of image to preserve from text overlapping
        $bottomSpacing          = 170;
        
        // Prepare top text
        $font       = (new Font($brag->description))
                ->file($fontFile)
                ->size(48)
                ->color('#fdf6e3')
                ->align('center')
                ->valign('middle');
        
        // Get text box dimensions
        $boxSize    = $font->getBoxSize();
        
        
        // Wave effect configuration
        $waveAmplitude  = 10;
        $waveLength     = floor($bgWidth / 2);
        
        // Prepare transparent canvas for text reserving space to crop wave effect artifacts
        //$waveText       = $imageManager->canvas($boxSize['width'], $boxSize['height'] + $waveAmplitude * 4);
        $waveText       = $imageManager->canvas($bgWidth, $bgHeight);
        
        // Put the text on canvas
        $font->applyToImage($waveText, $bgWidth/2, $waveAmplitude * 4);
        
        // Apply wave effect to Imagick instance
        $waveText->getCore()->waveImage($waveAmplitude, $waveLength);
        
        // Compensate wave effect oversising and artifacts
        $waveText->crop($waveText->width(), floor($waveText->height() - $waveAmplitude * 4))
                ->trim();
        
        // Check image size and resize if needed
        $paddingTop = $paddingBottom = $paddingLeft = $paddingRight = 20; // Paddings arround text
        
        if ($waveText->width() + $paddingLeft + $paddingRight > $bgWidth) {
            // Resize image to fit free space
            $waveText->resize($bgWidth - $paddingLeft - $paddingRight, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        if ($waveText->height() + $paddingTop + $paddingBottom > ($bgHeight - $bottomSpacing) / 2) {
            // Resize image to fit free space
            $waveText->resize(null, ($bgHeight - $bottomSpacing) / 2 - $paddingTop - $paddingBottom, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        // Calculate top text position
        $posY       = floor(($bgHeight - $bottomSpacing) / 2 / 2 - ($waveText->height() + $paddingTop + $paddingBottom) / 2 + $paddingTop);
        $posX       = floor($bgWidth / 2 - ($waveText->width() + $paddingLeft + $paddingRight) / 2 + $paddingLeft);
        
        // Place top text on image
        $backgroundImage->insert($waveText, 'top-left', $posX, $posY);
        
        // Prepare bottom text
        $bottomFont     = (new Font($customText))
                ->file($fontFile)
                ->size(48)
                ->color('#fdf6e3')
                ->align('center')
                ->valign('middle');
        
        
        //$bottomText     = $imageManager->canvas($bottomBoxSize['width'], $bottomBoxSize['height'] + 40);
        $bottomText     = $imageManager->canvas($bgWidth, $bgHeight);
        
        $bottomFont->applyToImage($bottomText, $bgWidth / 2, 20);
        
        $bottomText->trim();
        
        if ($bottomText->width() + $paddingLeft + $paddingRight > $bgWidth) {
            // Resize image to fit free space
            $bottomText->resize($bgWidth - $paddingLeft - $paddingRight, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        if ($bottomText->height() + $paddingTop + $paddingBottom > ($bgHeight - $bottomSpacing) / 2) {
            // Resize image to fit free space
            $bottomText->resize(null, ($bgHeight - $bottomSpacing) / 2 - $paddingTop - $paddingBottom, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        
        // Calculate top text position
        $posY       = floor(($bgHeight - $bottomSpacing) / 2 / 2 - ($bottomText->height() + $paddingTop + $paddingBottom) / 2 + $paddingTop + ($bgHeight - $bottomSpacing) / 2);
        $posX       = floor($bgWidth / 2 - ($bottomText->width() + $paddingLeft + $paddingRight) / 2 + $paddingLeft);
        
        $backgroundImage->insert($bottomText, 'top-left', $posX, $posY);



        // Todo: image filename should be unique and only stored temporarily (user should be able to upload/share to Twitter/Facebook)
        //$backgroundImage->save('images/brags/test.jpg');
        
        $filename   = uniqid() . '.jpg';
        
        session([
                'brag_filename' => $filename,
                'brag_id'       => $brag->id,
                'custom_text'   => $customText
                ]);
        
        $backgroundImage->save('images/brags/' . $filename);

        //return view('edit', ['brag' => $brag, 'customText' => $customText]);
        return redirect()->to('/show');
    }

    public function edit()
    {
        // $brag = Brag::where('id', request()->id)->get();
        // $brag = Brag::where('id', 1)->get();
        $customText = request()->comment;

        dd($request()->id);
        return view('edit', ['brag' => $brag, 'customText' => $customText]);
    }
    
    public function show(Session $session) {
        if (!$session->has('brag_filename') || !$session->has('brag_id')) {
            return redirect()->to('/');
        }
        
        try {
            $brag       = Brag::findOrFail($session->get('brag_id'));
        } catch (\Exception $ex) {
            return redirect()->to('/');
        }
        
        return view('show', ['brag' => $brag, 'brag_filename' => $session->get('brag_filename'), 'customText' => $session->get('custom_text', '')]);
    }


    public function postToFacebook(Facebook $fb, Request $request, Session $session) {
        if (!$session->has('brag_filename')) {
            // If user haven't created image -- redirect to main page
            return redirect()->to('/');
        }
        
        if ($request->has('error')) {
            // Unsuccessful oauth request, redirect to brag page
            return redirect()->to('/show')->withErrors('Facebook authentication error');
            
            // TODO: handle rerequest for permissions
        }
        
        if ($request->has('code')) {
            // If we have code in response, exchange it to user access token
            $userAccessToken        = $fb->getRedirectLoginHelper()->getAccessToken();
            
            if (!$userAccessToken) {
                return reditect()->to('/show');
            }
            
            // Remember user access token in session
            $session->put('fb-user-access-token', $userAccessToken);
        }
        
        if (!$session->has('fb-user-access-token')) {
            // If there is no access token -- init oauth flow, then redirect back to this method
            $redirectUrl    = $fb->getRedirectLoginHelper()
                    ->getLoginUrl(url('/postToFacebook'), ['user_photos', 'user_posts']);
            
            return redirect()->to($redirectUrl);
        }
        
        // Get user access token from session
        $userAccessToken            = $session->get('fb-user-access-token');
        
        try {
            // Make post to facebook
            $fb->post('/me/photos', [
                'source'        => $fb->fileToUpload( public_path('images/brags/' . session('brag_filename') ) )
            ], $userAccessToken);
            
            return redirect()->to('/show')->withMessage('Posted to Facebook');
            
        } catch (\Exception $ex) {
            //dd($ex);
            return redirect()->to('/show')->withErrors('Facebook posting error');
        }
    }
    
    public function twit(Twitter $twitter, Request $request, Session $session) {
        
        if (!$session->has('brag_filename')) {
            // If user haven't created image -- redirect to main page
            return redirect()->to('/');
        }
        
        if (!$session->has('tw-user-access-token')) {
            // Start oauth flow
            
            if (!$session->has('tw-user-request-token')) {
                // Get REQUEST token and redirect user to get permissions
                $twitter->reconfig(['token' => '', 'secret' => '']);
                
                $token          = $twitter->getRequestToken(url('/twit')); // Will be redirected to this method
                
                $session->put('tw-user-request-token', $token);
                
                if (isset($token['oauth_token_secret'])) {
                    // Send user to authorize permissions
                    $signInWithTwitter  = false;
                    $forceLogin         = false;
                    $url                = $twitter->getAuthorizeURL($token, $signInWithTwitter, $forceLogin);

                    return redirect()->to($url);
                } else {
                    return redirect()->to('/show')->withErrors('Couldnt get request token');
                }
            }
            
            $token              = $session->get('tw-user-request-token');
            
            if ($request->has('oauth_verifier')) {
                // We were redirected from twitter, time to exchange request token to access token
                
                $twitter->reconfig([
                        'token'     => $token['oauth_token'],
                        'secret'    => $token['oauth_token_secret']
                ]);
                
                $accessToken    = $twitter->getAccessToken($request->get('oauth_verifier'));
                
                if (!isset($accessToken['oauth_token_secret'])) {
                    return redirect()->to('/show')->withErrors('Couldnt authorize');
                }
                
                $session->put('tw-user-access-token', $accessToken);
                $session->forget('tw-user-request-token');
            
            } else {
                return redirect()->to('/show')->withErrors('Something wrong with twitter OAuth');
            }
        }
        
        $accessToken                = $session->get('tw-user-access-token');
        
        $twitter->reconfig([
                'token'         => $accessToken['oauth_token'],
                'secret'        => $accessToken['oauth_token_secret']
        ]);
        
        $media                      = $twitter->uploadMedia([
                'media'         => \Illuminate\Support\Facades\File::get(public_path('images/brags/' . $session->get('brag_filename')))
        ]);
        
        $twitter->postTweet([
                'status'        => '',
                'media_ids'     => $media->media_id_string
        ]);
        
        return redirect()->to('/show')->withMessage('Twitted');
    }
    
    private function getFontLineHeight($fontFile, $fontSize) {
        // Text height is returned the same for every font character
        $font               = new Font('M');
        $font->file($fontFile);
        $font->size($fontSize);
        $dimensions         = $font->getBoxSize();
        return $dimensions['height'];
    }
    
    private function countTextLines($text) {
        $lines              = preg_split('/\r?\n/', trim($text));
        return count($lines);
    }

}
