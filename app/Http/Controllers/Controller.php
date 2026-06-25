<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Moj Laravel API",
    version: "1.0.0",
    description: "Ovo je dokumentacija za moj Laravel API projekat"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Lokalni razvojni server"
)]
abstract class Controller
{
    //
}