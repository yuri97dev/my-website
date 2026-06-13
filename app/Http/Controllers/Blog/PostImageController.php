<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Blog\StorePostImageRequest;
use App\Models\PostImage;
use Symfony\Component\HttpFoundation\Response;

class PostImageController extends Controller
{
    public function index(Request $request)
    {
        $query = PostImage::query()
            ->select(["id", "post_id", "path", "created_at", "updated_at"])
            ->orderByDesc("id");

        if( $request->has("post_id") ) {
            $query->where("post_id", $request->integer("post_id"));
        }

        return response()->json([
            "images" => $query->get(),
        ], 200);
    }

    public function store(StorePostImageRequest $request)
    {
        $file = $request->file("image");
        $extension = $file->getClientOriginalExtension();

        $filename = Str::uuid()->toString() . "." . mb_strtolower($extension);
        $path = "blog/images/" . date("Y/m/d");

        $stored_path = $file->storeAs($path, $filename);

        $image = PostImage::create([
            "post_id" => $request->input("post_id"),
            "path" => $stored_path,
        ]);

        return response()->json([
            "image" => $image->only(["id", "post_id", "path", "created_at", "updated_at"]),
        ], 201);
    }

    public function show(PostImage $postImage)
    {
        abort_unless(
            Storage::disk("local")->exists($postImage->path),
            Response::HTTP_NOT_FOUND,
        );

        return response()->file(Storage::disk("local")->path($postImage->path));
    }

    public function destroy(PostImage $postImage)
    {
        Storage::disk("local")->delete($postImage->path);

        $postImage->delete();

        return response()->json([
            "message" => "Imagem removida com sucesso.",
        ], 200);
    }
}
