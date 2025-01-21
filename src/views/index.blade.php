<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('api-docs/swagger-ui.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('api-docs/index.css') }}" />
    <link rel="icon" type="image/png" href="{{ asset('api-docs/favicon-32x32.png') }}" sizes="32x32" />
    <link rel="icon" type="image/png" href="{{ asset('api-docs/favicon-16x16.png') }}" sizes="16x16" />
</head>

<body>
    <div id="swagger-ui"></div>
    <script src="{{ asset('api-docs/swagger-ui-bundle.js') }}" charset="UTF-8"></script>
    <script src="{{ asset('api-docs/swagger-ui-standalone-preset.js') }}" charset="UTF-8"></script>
    <script charset="UTF-8">
        window.onload = function() {
            const apiDocsUrl = '{{ asset('api-docs/api-docs.json') }}';
            window.ui = SwaggerUIBundle({
                url: apiDocsUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>

</html>
