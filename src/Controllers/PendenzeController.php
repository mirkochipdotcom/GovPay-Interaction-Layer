<?php
namespace App\Controllers;

class PendenzeController {
    public function index($request, $response) {
        // Temporary: re-use legacy test.php output
        ob_start();
        include __DIR__ . '/../test.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }
}
