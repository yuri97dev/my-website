<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Blog\AssociatePostImagesRequest;
use App\Http\Requests\Blog\StorePostImageRequest;
use App\Models\PostImage;
use Symfony\Component\HttpFoundation\Response;

class PostImageController extends Controller
{
    public function index(Request $request)
    {
        $query = PostImage::query()
            ->select(["id", "post_id", "created_at", "updated_at"])
            ->limit(100)
            ->orderByDesc("created_at");

        if( $request->has("post_id") ) {
            $post_ids = (array) $request->input("post_id");

            $query->where(function ($query) use ($post_ids) {
                $query->whereIn("post_id", $post_ids);
                $query->orWhereNull("post_id");
            });
        } else {
            if( $request->has("available") ) {
                $query->whereNull("post_id");
            }
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
            "path" => "/" . ltrim($stored_path, "/"),
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

    public function associate(AssociatePostImagesRequest $request)
    {
        $post_id = (int) $request->input("post_id");

        $paths = collect($request->input("paths", []))
            ->map(fn ($path) => $this->normalizeImagePath($path))
            ->filter()
            ->unique()
            ->values();

        if( $paths->isEmpty() ) return response()->json([
            "associated" => 0,
        ], 200);

        $ids = $paths
            ->map(fn ($path) => $this->extractImageIdFromPath($path))
            ->filter()
            ->unique()
            ->values();

        $associated = PostImage::query()
            ->whereNull("post_id")
            ->where(function ($query) use ($paths, $ids) {
                $query->whereIn("path", $paths);

                if( $ids->isNotEmpty() ) {
                    $query->orWhereIn("id", $ids);
                }
            })
            ->update(["post_id" => $post_id]);

        return response()->json([
            "associated" => $associated,
        ], 200);
    }

    private function normalizeImagePath(string $path): ?string
    {
        $path = trim($path);

        if( $path === "" ) {
            return null;
        }

        if( Str::startsWith($path, ["http://", "https://"]) ) {
            $path = parse_url($path, PHP_URL_PATH) ?: $path;
        }

        $path = "/" . ltrim($path, "/");

        return $path !== "/" ? $path : null;
    }

    private function extractImageIdFromPath(string $path): ?string
    {
        if( preg_match("#^/blog/images/([0-9a-fA-F-]{36})$#", $path, $matches) ) {
            return $matches[1];
        }

        return null;
    }
}
