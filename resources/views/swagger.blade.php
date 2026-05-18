<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-standalone-preset.js"></script>
</head>
<body>
<div id="swagger-ui"></div>

@php

    $pathToJson = public_path('swagger.json');
    $swaggerContent = '';


    if (file_exists($pathToJson)) {
        $swaggerContent = file_get_contents($pathToJson);
    } else {
        $swaggerContent = json_encode([
            "openapi" => "3.0.0",
            "info" => [
                "title" => "Пример API",
                "version" => "1.0.0"
            ],
            "paths" => []
        ]);
    }

    $jsonForJs = json_encode(json_decode($swaggerContent));
@endphp

<script>
    const swaggerSpec = {!! $jsonForJs !!};

    const ui = SwaggerUIBundle({
        spec: swaggerSpec,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: "StandaloneLayout"
    });
</script>
</body>
</html>