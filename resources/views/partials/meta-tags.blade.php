@if( !empty($meta["title"]) )
    <title>{{ config("app.name") }} - {{ $meta["title"] }}</title>
@endif

@if( !empty($meta["description"]) )
    <meta name="description" content="{{ $meta['description'] }}">
@endif

@if( !empty($meta["og_title"]) )
    <meta property="og:title" content="{{ $meta['og_title'] }}">
@endif

@if( !empty($meta['og_description']) )
    <meta property="og:description" content="{{ $meta['og_description'] }}">
@endif

@if( !empty($meta['og_image']) )
    <meta property="og:image" content="{{ $meta['og_image'] }}">
@endif

<meta property="og:type" content="website">