<?php
namespace App\Controllers;

use App\Auth\UserRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UsersController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function profile(Request $request, Response $response, array $args)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        // Dati utente corrente
        $request = $request->withAttribute('profile', $user);
        return $request;
    }

    private function assertAdmin(): void
    {
        $u = $_SESSION['user'] ?? null;
        if (!$u || !in_array($u['role'], ['admin','superadmin'], true)) {
            throw new \RuntimeException('Forbidden');
        }
    }

    public function index(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $list = $this->users->listAll();
        $request = $request->withAttribute('users', $list);
        return $request;
    }

    public function new(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        return $request;
    }

    public function create(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $data = (array)($request->getParsedBody() ?? []);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $role = in_array(($data['role'] ?? 'user'), ['user','admin','superadmin'], true) ? $data['role'] : 'user';
        if ($email === '' || $password === '') {
            return $request->withAttribute('error', 'Email e password sono obbligatorie');
        }
        if ($this->users->findByEmail($email)) {
            return $request->withAttribute('error', 'Email già in uso');
        }
        $this->users->insertUser($email, $password, $role, $firstName, $lastName);
    $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Utente creato con successo'];
        return $response->withHeader('Location', '/users')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $id = (int)($args['id'] ?? 0);
        $user = $this->users->findById($id);
        if (!$user) {
            throw new \RuntimeException('Not Found');
        }
        $request = $request->withAttribute('edit_user', $user);
        return $request;
    }

    public function update(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $id = (int)($args['id'] ?? 0);
        $data = (array)($request->getParsedBody() ?? []);
        $email = trim($data['email'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $role = in_array(($data['role'] ?? 'user'), ['user','admin','superadmin'], true) ? $data['role'] : 'user';
        $password = trim($data['password'] ?? '');
        if ($email === '') {
            $user = $this->users->findById($id);
            return $request->withAttribute('error', 'Email obbligatoria')
                           ->withAttribute('edit_user', $user);
        }
        $this->users->updateUser($id, $email, $role, $firstName, $lastName);
        if ($password !== '') {
            $this->users->updatePasswordById($id, $password);
        }
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Utente aggiornato'];
        return $response->withHeader('Location', '/users')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $id = (int)($args['id'] ?? 0);
        $this->users->deleteById($id);
    $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Utente eliminato'];
        return $response->withHeader('Location', '/users')->withStatus(302);
    }
}
