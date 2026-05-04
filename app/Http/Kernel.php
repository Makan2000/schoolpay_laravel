protected $routeMiddleware = [
    // ...
    'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
    'parent' => \App\Http\Middleware\ParentMiddleware::class,
];
