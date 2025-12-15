<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\SliderImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderImageController extends Controller
{
    public function index(Slider $slider)
    {
        return view('admin.sliders.images', [
            'slider' => $slider,
            'images' => $slider->images()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, Slider $slider)
    {
        $data = $request->validate([
            'image' => 'required|image|max:4096',
            'heading' => 'nullable|string',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string',
            'button_link' => 'nullable|string',
        ]);

        $data['slider_id'] = $slider->id;
        $data['sort_order'] = $slider->images()->count();
        $data['image'] = $request->file('image')->store('sliders', 'public');

        SliderImage::create($data);

        return back()->with('success', 'Slide added.');
    }

    public function edit(SliderImage $image)
    {
        return view('admin.sliders.edit-image', [
            'image' => $image,
            'slider' => $image->slider
        ]);
    }

    public function update(Request $request, SliderImage $image)
    {
        $data = $request->validate([
            'image' => 'nullable|image|max:4096',
            'heading' => 'nullable|string',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string',
            'button_link' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($image->image);
            $data['image'] = $request->file('image')->store('sliders', 'public');
        }

        $image->update($data);

        return redirect()->route('admin.sliders.images.index', $image->slider_id)
                         ->with('success', 'Slide updated successfully.');
    }

    public function destroy(SliderImage $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();
        return back()->with('success', 'Slide removed.');
    }

    // AJAX sort update
    public function sort(Request $request)
    {
        $order = $request->order; // array of IDs in new order

        foreach ($order as $index => $id) {
            SliderImage::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }
}