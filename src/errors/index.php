<?php
/**
 * File deprecato.
 * La gestione degli errori (404, 403, ecc.) è ora demandata a Slim tramite l'ErrorMiddleware
 * e il template Twig `templates/errors/404.html.twig`.
 * Questo file rimane solo come promemoria e potenziale fallback se referenziato da configurazioni legacy.
 */
http_response_code(404);
echo '404 Not Found';
