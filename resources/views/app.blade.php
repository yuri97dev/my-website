<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @if( !empty($page["props"]["meta"]) )
            @include("partials.meta-tags", [ "meta"=> $page["props"]["meta"] ])
        @else
            <title>{{ config("app.name") }}</title>
        @endif

        @routes
        @vite(["resources/css/app.css", "resources/js/app.js"])

        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>