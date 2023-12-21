<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Posting;
use App\Models\User;
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

    public function show(Posting $posting, Request $request)
    {
        return view('postings.show', compact('posting'));
    }

    public function apply(Posting $posting, Request $request)
    {
        $posting->clicks()
            ->create([
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);

        return redirect()->to($posting->apply_link);
    }

    public function create()
    {
        return view('postings.create');
    }

    public function store(Request $request)
    {
        $validateOnArray = [
            'title' => 'required',
            'company' => 'required',
            'logo' => 'file|max:2048',
            'location' => 'required',
            'apply_link' => 'required|url',
            'content' => 'required',
            'payment_method_id' => 'required',
        ];

        if(! auth()->check())
        {
            $validateOnArray = array_merge($validateOnArray, [
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:5',
                'name' => 'required|max:255',
            ]);
        }

        $request->validate($validateOnArray);

        $user = auth()->user();

        if(! $user)
        {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $user->createAsStripeCustomer();

            auth()->login($user);
        }

        try
        {
            $amount = 9900;
            if($request->is_highlighted)
            {
                $amount += 1900;
            }

            $user->charge($amount, $request->payment_method_id);

            $md = new \ParsedownExtra();

            $posting = $user->postings()
                ->create([
                    'title' => $request->title,
                    'slug' => Str::slug($request->title) . '-' . rand(1111, 9999),
                    'company' => $request->company,
                    'logo' => basename( $request->file('logo')->store('public') ),
                    'location' => $request->location,
                    'apply_link' => $request->apply_link,
                    'content' => $md->text($request->content),
                    'is_highlighted' => $request->filled('is_highlighted'),
                    'is_active' => true,
                ]);

            foreach(explode(',', $request->tags) as $requestTag)
            {
                $tag = Tag::firstOrCreate([
                    'slug' => Str::slug(trim($requestTag))
                ], [
                    'name' => ucwords(trim($requestTag))
                ]);

                $tag->postings()->attach($posting->id);
            }
        }
        catch(\Exception $e)
        {
            return redirect()->back()
                ->withErrors([
                    'error' => $e->getMessage(),
                ]);
        }

        return redirect()->route('dashboard');
    }
}
