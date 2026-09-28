<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function terms(): View
    {
        return $this->show('Terms and Conditions', 'terms-and-conditions.md');
    }

    public function privacy(): View
    {
        return $this->show('Privacy Policy', 'privacy-policy.md');
    }

    private function show(string $title, string $filename): View
    {
        $markdown = File::get(base_path('docs/legal/'.$filename));

        return view('legal.show', [
            'title' => $title,
            'content' => Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
