<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;
#[
    OA\Info(version: "1.0.0", description: "Laravel Advanced Api Documantation", title: "Laravel Advanced Api"),
    OA\Server(url: "http://127.0.0.1:8000/api", description: "Development Server"),
    OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", name: "Authorization", in: "header", scheme: "bearer"),
]
abstract class Controller
{
    //
}
