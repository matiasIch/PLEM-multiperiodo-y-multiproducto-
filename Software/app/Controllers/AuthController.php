<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Router;
use App\Core\View;

class AuthController
{
    public function showLanding()
    {
        if (Auth::check()) {
            Router::redirect('/app');
            return;
        }

        Router::redirect('/login');
    }

    public function showLogin()
    {
        if (Auth::isInitialSetupNeeded()) {
            Router::redirect('/setup');
            return;
        }

        if (Auth::check()) {
            Router::redirect('/app');
            return;
        }

        View::render('pages/login', [
            'title' => 'Iniciar Sesión - FARMAFÉ'
        ], 'auth');
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Session::setFlash('error', 'Por favor ingrese su nombre de usuario y contraseña.');
            Router::redirect('/login');
            return;
        }

        if (Auth::attempt($username, $password)) {
            Session::setFlash('success', 'Bienvenido al sistema FARMAFÉ.');
            Router::redirect('/app');
            return;
        }

        Session::setFlash('error', 'Credenciales inválidas o cuenta inactiva. Por favor intente nuevamente.');
        Router::redirect('/login');
    }

    public function logout()
    {
        Auth::logout();
        Session::setFlash('info', 'Ha cerrado la sesión correctamente.');
        Router::redirect('/login');
    }

    public function showSetup()
    {
        if (!Auth::isInitialSetupNeeded()) {
            Session::setFlash('info', 'El sistema ya cuenta con un administrador inicial registrado.');
            Router::redirect('/login');
            return;
        }

        View::render('pages/setup', [
            'title' => 'Configuración Inicial - Primer Administrador'
        ], 'auth');
    }

    public function setup()
    {
        if (!Auth::isInitialSetupNeeded()) {
            Session::setFlash('error', 'El asistente inicial ya fue completado.');
            Router::redirect('/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($fullName) || empty($email) || empty($password)) {
            Session::setFlash('error', 'Todos los campos son obligatorios para configurar el primer administrador.');
            Router::redirect('/setup');
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setFlash('error', 'Las contraseñas ingresadas no coinciden.');
            Router::redirect('/setup');
            return;
        }

        $result = Auth::createInitialAdmin($username, $password, $fullName, $email);
        if ($result['success']) {
            // Iniciar sesion inmediatamente con el nuevo admin
            Auth::attempt($username, $password);
            Session::setFlash('success', 'Cuenta de administrador creada exitosamente. El asistente inicial ha sido bloqueado.');
            Router::redirect('/app');
            return;
        }

        Session::setFlash('error', $result['error'] ?? 'No se pudo crear la cuenta inicial.');
        Router::redirect('/setup');
    }
}

