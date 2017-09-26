<?php
namespace MyForksFiles\CliPack\Http\Middleware;

use App;
use Auth;
use Log;
use Closure;

/**
 * Class AuthBasic
 * @package MyForksFiles\CliPack\Http\Middleware
 *
 *- -***
 */
class AuthBasic
{
    /**
     * Default credentials user name, pass value, can be replaced via .env AUTH_PW.
     *
     * @var string
     */
    protected $credentials = [
        'user' => 'user',
        'pass' => 'secretPassword',
    ];

    /**
     * @var array
     */
    protected $authHeaders = ['WWW-Authenticate' => 'Basic'];

    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Routing\ResponseFactory|mixed|\Symfony\Component\HttpFoundation\Response|void
     */
    public function handle($request, Closure $next)
    {
        if (App::environment() == 'production') {
            return;
        }

        $this->credentials = $this->getCredentials();

        $user = $request->header('PHP_AUTH_USER');
        $pass = $request->header('PHP_AUTH_PW');

        if (empty($user) && empty($pass)) {
            Log::warning('Login required: ' . $_SERVER['REMOTE_ADDR']);
            return $this->authResponse('Login required!');
        }

        if (!$this->basicValidation($user, $pass)) {
            Log::warning('Invalid credentials: ' . $_SERVER['REMOTE_ADDR'] . ';' . $user . ';' . $pass);
            return $this->authResponse('Invalid credentials.');
        }

        return $next($request);
    }

    /**
     * Replace credentials from .env.
     *
     * @return array
     */
    protected function getCredentials()
    {
        $user = env('AUTH_USER', $this->credentials['user']);
        $pass = env('AUTH_PW', $this->credentials['pass']);

        return [
            'user' => $user,
            'pass' => $pass,
        ];
    }

    /**
     * @param $msg
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function authResponse($msg)
    {
        return response(
            'Whoops, looks like something went wrong: ' . $msg,
            401,
            $this->authHeaders
        );
    }

    /**
     * @param $user
     * @param $pass
     * @return bool
     */
    private function basicValidation($user, $pass)
    {
        return (
            $user == $this->credentials['user']
            &&
            $pass == $this->credentials['pass']
        ) ? true : false;
    }
}