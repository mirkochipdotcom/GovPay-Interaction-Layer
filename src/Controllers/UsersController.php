<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
namespace App\Controllers;

use App\Auth\UserRepository;
use App\Logger;
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
        $countSuperadmins = $this->users->countByRole('superadmin');
        $request = $request->withAttribute('users', $list)->withAttribute('count_superadmins', $countSuperadmins);
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
        // Prevent plain admins from editing superadmin accounts
        $current = $_SESSION['user'] ?? null;
        if (($current['role'] ?? '') === 'admin' && ($user['role'] ?? '') === 'superadmin') {
            // Return request with an error attribute so the route can render an appropriate message
            $request = $request->withAttribute('error', 'Non puoi modificare un account superadmin')->withAttribute('edit_user', null);
            return $request;
        }

        // Determine if a superadmin editing their own account can demote themselves
        $canDemoteSelf = true;
        if ($current && isset($current['id']) && (int)$current['id'] === $id && ($current['role'] ?? '') === 'superadmin') {
            $count = $this->users->countByRole('superadmin');
            $canDemoteSelf = ($count > 1);
        }

        $request = $request->withAttribute('edit_user', $user)->withAttribute('can_demote_self', $canDemoteSelf);
        return $request;
    }

    public function update(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $id = (int)($args['id'] ?? 0);
        // Prevent admins from updating superadmin accounts
        $target = $this->users->findById($id);
        $current = $_SESSION['user'] ?? null;
        if ($target && ($target['role'] ?? '') === 'superadmin' && ($current['role'] ?? '') === 'admin') {
            $request = $request->withAttribute('error', 'Non puoi modificare un account superadmin')->withAttribute('edit_user', $target);
            return $request;
        }
        $data = (array)($request->getParsedBody() ?? []);
        $email = trim($data['email'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        // Handle submitted role
        $submittedRole = in_array(($data['role'] ?? 'user'), ['user','admin','superadmin'], true) ? $data['role'] : 'user';

        // Prevent an admin from changing their own role (no escalation/demotion)
        if ($current && isset($current['id']) && (int)$current['id'] === $id && ($current['role'] ?? '') === 'admin') {
            // Ignore submitted role change and keep target's existing role
            $role = $target['role'] ?? 'admin';
        } elseif ($current && isset($current['id']) && (int)$current['id'] === $id && ($current['role'] ?? '') === 'superadmin' && $submittedRole !== 'superadmin') {
            // Superadmin attempting to declass themselves: allow only if there is another superadmin
            $count = $this->users->countByRole('superadmin');
            if ($count <= 1) {
                // Block and return an informative error
                Logger::getInstance()->warning('Attempt to self-demote last superadmin blocked', ['current_id' => $current['id'] ?? null, 'target_id' => $id]);
                return $request->withAttribute('error', 'Devi mantenere almeno un altro superadmin prima di declassarti')->withAttribute('edit_user', $target);
            }
            $role = $submittedRole;
        } else {
            $role = $submittedRole;
        }
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
        // If the current logged-in user updated their own profile, refresh session data
        if ($current && isset($current['id']) && (int)$current['id'] === $id) {
            $fresh = $this->users->findById($id);
            if ($fresh) {
                // Ensure session contains the same shape we expect elsewhere
                $_SESSION['user'] = $fresh;
            }
        }
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Utente aggiornato'];
        return $response->withHeader('Location', '/users')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $this->assertAdmin();
        $id = (int)($args['id'] ?? 0);
        $target = $this->users->findById($id);
        $current = $_SESSION['user'] ?? null;
        // Prevent users (admin or superadmin) from deleting themselves
        if ($current && isset($current['id']) && (int)$current['id'] === $id) {
            $_SESSION['flash'][] = ['type' => 'danger', 'text' => 'Non puoi eliminare il tuo account'];
            return $response->withHeader('Location', '/users')->withStatus(302);
        }

        if ($target && ($target['role'] ?? '') === 'superadmin' && ($current['role'] ?? '') === 'admin') {
            // Block deletion by plain admin
            $_SESSION['flash'][] = ['type' => 'danger', 'text' => 'Non puoi eliminare un account superadmin'];
            Logger::getInstance()->warning('Blocked delete by admin on superadmin', ['current_id' => $current['id'] ?? null, 'target_id' => $id]);
            return $response->withHeader('Location', '/users')->withStatus(302);
        }

        // Prevent deleting the last superadmin in the system
        if ($target && ($target['role'] ?? '') === 'superadmin') {
            $count = $this->users->countByRole('superadmin');
            if ($count <= 1) {
                $_SESSION['flash'][] = ['type' => 'danger', 'text' => 'Impossibile eliminare l\'ultimo superadmin. Assegna il ruolo di superadmin a un altro utente prima di procedere.'];
                Logger::getInstance()->warning('Attempt to delete last superadmin blocked', ['current_id' => $current['id'] ?? null, 'target_id' => $id]);
                return $response->withHeader('Location', '/users')->withStatus(302);
            }
        }

        $this->users->deleteById($id);
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Utente eliminato'];
        return $response->withHeader('Location', '/users')->withStatus(302);
    }
}
