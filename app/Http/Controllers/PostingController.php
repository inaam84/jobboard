<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Posting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PostingController extends Controller
{
    public function index(Request $request)
    {
        $query = Posting::query()
            ->active()
            ->latest();

        $tags = Tag::query()
            ->orderBy('name')
            ->get();

        if( $request->has('s') )
        {
            $s = trim( $request->get('s') );

            $query->where(function (Builder $builder) use ($s) {
                $builder
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%");
            });
        }

        if($request->has('tag'))
        {
            $tag = $request->get('tag');
            $query->whereHas('tags', function(Builder $builder) use ($tag){
                $builder->where('slug', $tag);
            });
        }

        $postings = $query->get();

        return view('postings.index', compact('postings', 'tags'));
    }
}
