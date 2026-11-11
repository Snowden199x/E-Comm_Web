<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function terms(Request $request): View
    {
        return $this->show($request, 'Terms and Conditions', 'terms-and-conditions.md');
    }

    public function privacy(Request $request): View
    {
        return $this->show($request, 'Privacy Policy', 'privacy-policy.md');
    }

    private function show(Request $request, string $title, string $filename): View
    {
        $path = base_path('docs/legal/'.$filename);
        $markdown = File::get($path);

        $content = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        // Add stable ids to the section headings so the "On this page" list can link to them.
        $sections = [];
        $content = preg_replace_callback('/<h2>(.*?)<\/h2>/s', function ($match) use (&$sections) {
            $label = trim(strip_tags($match[1]));
            $id = Str::slug($label) ?: 'section-'.(count($sections) + 1);
            $sections[] = ['label' => $label, 'id' => $id];

            return '<h2 id="'.$id.'">'.$match[1].'</h2>';
        }, $content);

        return view('legal.show', [
            'title' => $title,
            'content' => $content,
            'sections' => $sections,
            'updatedAt' => Carbon::createFromTimestamp(File::lastModified($path))->format('F j, Y'),
            'homeUrl' => $this->homeUrl($request),
        ]);
    }

    /** Where the logo and the Back button go when there is no page to return to. */
    private function homeUrl(Request $request): string
    {
        if (auth('admin')->check()) {
            return route('admin.dashboard');
        }

        return match ($request->user()?->role) {
            'buyer' => route('buyer.dashboard'),
            'seller' => route('seller.dashboard'),
            'logistics_center' => route('logistics.dashboard'),
            default => url('/'),
        };
    }
}
