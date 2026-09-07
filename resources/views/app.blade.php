<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        <link rel="manifest" href="/favicons/site.webmanifest">

        <style>
            #loading-container {
                display: none;
            }

            #loading-container.opened {
                display: flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: #000;
                z-index: 9999;
            }

            #loading-container img {
                width: 30dvw;
                min-width: 200px;
                max-width: 300px;
                user-select: none;
                animation: float-effect 3s ease-in-out infinite;
            }

            @keyframes float-effect {
                0%, 100% {
                    transform: translateY(0);
                    opacity: 0.8;
                }
                50% {
                    transform: translateY(-20px);
                    opacity: 1;
                }
            }
        </style>

        @if( !empty($page["props"]["meta"]) )
            @include("partials.meta-tags", [ "meta"=> $page["props"]["meta"] ])
        @else
            <title>{{ config("app.name") }}</title>
        @endif

        @php
            if (auth()->user()?->isSystemAdmin()) {
                config(["ziggy.except" => []]);
            }
        @endphp

        @routes
        @vite(["resources/css/app.css", "resources/js/app.js"])

        <x-inertia::head />
    </head>
    <body>
        <div id="loading-container" class="opened">
            <img src="/images/logo.webp" alt="Logo do site">
        </div>

        <x-inertia::app />
    </body>
</html>