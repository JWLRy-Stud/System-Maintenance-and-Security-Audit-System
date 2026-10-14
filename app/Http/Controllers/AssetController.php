<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with('assignedUser')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('asset_tag', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('asset_tag')->paginate(15)->withQueryString();

        return view('assets.index', compact('assets'));
    }

    public function create() { return view('assets.form', ['asset' => new Asset, 'users' => User::orderBy('name')->get()]); }

    public function store(AssetRequest $request)
    {
        $asset = Asset::create($request->validated());
        ActivityLogService::record('asset.created', $asset);

        return redirect()->route('assets.show', $asset)->with('ok', 'Asset created.');
    }

    public function show(Request $request, Asset $asset)
    {
        $asset->load('assignedUser');

        // Only the maintenance history this user is allowed to open (same rule as the maintenance list).
        $history = MaintenanceRequest::with('technician')->visibleTo($request->user())
            ->where('asset_id', $asset->id)->latest()->get();

        return view('assets.show', compact('asset', 'history'));
    }

    public function edit(Asset $asset) { return view('assets.form', ['asset' => $asset, 'users' => User::orderBy('name')->get()]); }

    public function update(AssetRequest $request, Asset $asset)
    {
        $asset->update($request->validated());
        ActivityLogService::record('asset.updated', $asset);

        return redirect()->route('assets.show', $asset)->with('ok', 'Asset updated.');
    }
}
