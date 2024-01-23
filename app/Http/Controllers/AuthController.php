<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CrCore\WsUsuarios;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function authenticateV2(Request $request): JsonResponse
    {
        $credentials = $request->only('credentials');
        $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
//        dd(gettype($credentials['credentials']), $credentials['credentials']);
//        dd(Crypt::decryptString($credentials['credentials']));
        dd(openssl_decrypt(base64_decode($credentials['credentials']), 'aes-256-cbc', 'cr3dit3k', OPENSSL_RAW_DATA));

        try {
            JWTAuth::factory()->setTTL(120);
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'invalid credentials',
                    'code' => 400,
                    'status' => false
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'could not create token',
                'code' => 500,
                'status' => false
            ], 500);
        }
        return response()->json([
            'message' => 'success',
            'data' => compact('token'),
            'code' => 200,
            'status' => true
            ]);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $credentials = $request->only('username', 'password');
        try {
            JWTAuth::factory()->setTTL(120);
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'invalid credentials',
                    'code' => 400,
                    'status' => false
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'could not create token',
                'code' => 500,
                'status' => false
            ], 500);
        }
        return response()->json([
            'message' => 'success',
            'data' => compact('token'),
            'code' => 200,
            'status' => true
            ]);
    }

    public function refreshToken(): JsonResponse
    {
        $token = JWTAuth::getToken();
        if(!$token){
            return response()->json([
                'message' => 'Token not provided',
                'code' => 400,
                'status' => false
            ], 400);
        }
        try{
            $token = JWTAuth::refresh($token);
        }catch(TokenInvalidException $e){
            return response()->json([
                'message' => 'The token is invalid',
                'code' => 500,
                'status' => false
            ], 500);
        }
        return response()->json([
            'message' => 'success',
            'data' => compact('token'),
            'code' => 200,
            'status' => true
        ], 200);
    }

    public function getAuthenticatedUser(): JsonResponse
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'message' => 'user not found',
                    'code' => 404,
                    'status' => false
                ], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'token expired',
                'code' => 500,
                'status' => false
            ], 500);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'token invalid',
                'code' => 500,
                'status' => false
            ], 500);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'token absent',
                'code' => 500,
                'status' => false
            ], 500);
        }
        return response()->json([
            'message' => 'success',
            'data' => compact('user'),
            'code' => 200,
            'status' => true
        ], 200);
    }

    /**
     * @param $username
     * @param $password
     * @return JsonResponse
     */
    public function register($username, $password): JsonResponse
    {
        if (!$username && !$password) {
            return response()->json([
                'message' => 'success',
                'code' => 400,
                'status' => true
            ], 400);
        }

        $user = WsUsuarios::create([
            'username' => $username,
            'password' => bcrypt($password),
            'negocio_id' => 4,
            'estado' => 'HAB'
        ]);

        $token = JWTAuth::fromUser($user);

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response()->json([
            'message' => 'success',
            'data' => $response,
            'code' => 201,
            'status' => true
        ], 201);
    }
}
