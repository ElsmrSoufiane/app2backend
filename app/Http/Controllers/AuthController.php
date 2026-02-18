<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private $config = [
        'dev_mode' => false,
        'min_password_length' => 8,
        'gmail' => [
            'username' => 'eemssoufiane@gmail.com',
            'password' => 'hmjdcatkbgledfhl',
            'from_name' => 'مختصر الأرباح',
        ],
    ];

    private function addCorsHeaders($response)
    {
        return $response->header('Access-Control-Allow-Origin', '*')
                       ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                       ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                       ->header('Access-Control-Allow-Credentials', 'true');
    }

    private function configureGmail()
    {
        try {
            Log::info('[GMAIL CONFIG] Starting Gmail configuration');
            
            $gmailUsername = $this->config['gmail']['username'];
            $gmailPassword = $this->config['gmail']['password'];
            $fromName = $this->config['gmail']['from_name'];
            
            Config::set('mail.default', 'smtp');
            Log::info('[GMAIL CONFIG] Set mail.default to smtp');

            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => $gmailUsername,
                'password' => $gmailPassword,
                'timeout' => 30,
            ]);
            Log::info('[GMAIL CONFIG] Set mail.mailers.smtp', [
                'host' => 'smtp.gmail.com',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => $gmailUsername
            ]);

            Config::set('mail.from', [
                'address' => $gmailUsername,
                'name' => $fromName,
            ]);
            Log::info('[GMAIL CONFIG] Set mail.from', [
                'address' => $gmailUsername,
                'name' => $fromName
            ]);

            app('mail.manager')->forgetMailers();
            Log::info('[GMAIL CONFIG] Mail manager cache cleared');

            return ['success' => true, 'message' => 'Gmail configured successfully'];
            
        } catch (\Throwable $e) {
            Log::error('[GMAIL CONFIG ERROR] ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function register(Request $request){
        Log::info('[REGISTER] Starting registration', ['email' => $request->email]);
        
        try {
            $data = $request->validate([
                'name' => 'required|string|max:120',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:' . $this->config['min_password_length'],
            ]);

            Log::info('[REGISTER] Validation passed');

            $user = User::create([
                'name'=>$data['name'],
                'email'=>$data['email'],
                'password'=>Hash::make($data['password']),
            ]);

            Log::info('[REGISTER] User created', ['user_id' => $user->id]);

            $token = $user->createToken('web')->plainTextToken;

            return $this->addCorsHeaders(response()->json([
                'success' => true,
                'user'=> $this->safeUser($user),
                'token'=> $token,
            ], 201));

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[REGISTER] Validation error', ['errors' => $e->errors()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422));
            
        } catch (\Throwable $e) {
            Log::error('[REGISTER] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500));
        }
    }

    public function login(Request $request){
        Log::info('[LOGIN] Attempt', ['email' => $request->email]);
        
        try {
            $data = $request->validate([
                'email'=>'required|email',
                'password'=>'required|string',
            ]);

            $user = User::where('email',$data['email'])->first();
            
            if(!$user || !Hash::check($data['password'],$user->password)){
                Log::warning('[LOGIN] Failed', ['email' => $data['email']]);
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'message'=>'Invalid credentials'
                ], 401));
            }

            Log::info('[LOGIN] Success', ['user_id' => $user->id]);

            $token = $user->createToken('web')->plainTextToken;

            return $this->addCorsHeaders(response()->json([
                'success' => true,
                'user'=> $this->safeUser($user),
                'token'=> $token,
            ]));

        } catch (\Throwable $e) {
            Log::error('[LOGIN] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500));
        }
    }

    public function logout(Request $request){
        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('[LOGOUT] Success', ['user_id' => $request->user()->id]);
            return $this->addCorsHeaders(response()->json(['success'=>true]));
        } catch (\Throwable $e) {
            Log::error('[LOGOUT] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }

    public function changePassword(Request $request){
        try {
            $user = $request->user();
            $data = $request->validate([
                'current_password'=>'required|string',
                'password'=>'required|string|min:' . $this->config['min_password_length'] . '|confirmed',
            ]);

            if(!Hash::check($data['current_password'],$user->password)){
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'message'=>'كلمة المرور الحالية غير صحيحة'
                ], 400));
            }

            $user->password = Hash::make($data['password']);
            $user->save();
            
            Log::info('[CHANGE PASSWORD] Success', ['user_id' => $user->id]);
            
            return $this->addCorsHeaders(response()->json(['success'=>true]));

        } catch (\Throwable $e) {
            Log::error('[CHANGE PASSWORD] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }

    public function forgotPassword(Request $request){
        Log::info('[FORGOT PASSWORD] Started', ['email' => $request->email]);
        
        try {
            $data = $request->validate(['email'=>'required|email']);
            Log::info('[FORGOT PASSWORD] Validation passed');

            $user = User::where('email',$data['email'])->first();
            
            if(!$user) {
                Log::warning('[FORGOT PASSWORD] User not found', ['email' => $data['email']]);
                return $this->addCorsHeaders(response()->json([
                    'success' => true,
                    'message' => 'If email exists, reset link will be sent'
                ]));
            }
            
            Log::info('[FORGOT PASSWORD] User found', [
                'user_id' => $user->id, 
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'yes' : 'no'
            ]);

            $token = Str::random(64);
            $user->password_reset_token = $token;
            $user->password_reset_expires_at = now()->addMinutes(30);
            $user->save();
            
            Log::info('[FORGOT PASSWORD] Token saved to database', [
                'token_length' => strlen($token),
                'expires_at' => $user->password_reset_expires_at
            ]);

            // Check if dev mode is on
            if ($this->config['dev_mode']) {
                Log::info('[FORGOT PASSWORD] Dev mode ON - returning token');
                
                $resetLink = 'http://localhost:3000/reset?email=' . urlencode($user->email) . '&token=' . $token;
                
                return $this->addCorsHeaders(response()->json([
                    'success' => true, 
                    'dev_token' => $token,
                    'reset_link' => $resetLink,
                    'message' => 'DEV MODE: Use this token to reset password'
                ]));
            }

            // Production mode - try to send email
            Log::info('[FORGOT PASSWORD] Production mode - attempting to send email');

            // Configure Gmail
            $configResult = $this->configureGmail();
            if (!$configResult['success']) {
                Log::error('[FORGOT PASSWORD] Gmail configuration failed', $configResult);
                throw new \Exception('Gmail configuration failed: ' . $configResult['message']);
            }

            $resetLink = 'http://localhost:3000/reset?email=' . urlencode($user->email) . '&token=' . $token;
            Log::info('[FORGOT PASSWORD] Reset link created', ['link' => $resetLink]);

            // Check if email template exists
            $templatePath = resource_path('views/emails/password-reset.blade.php');
            if (!file_exists($templatePath)) {
                Log::error('[FORGOT PASSWORD] Email template not found', ['path' => $templatePath]);
                
                // Try to create a simple template
                $templateDir = resource_path('views/emails');
                if (!is_dir($templateDir)) {
                    mkdir($templateDir, 0755, true);
                }
                
                $simpleTemplate = '<!DOCTYPE html>
<html>
<head><title>Password Reset</title></head>
<body>
<h2>مرحباً {{ $user->name }}</h2>
<p>انقر على الرابط التالي لإعادة تعيين كلمة المرور:</p>
<p><a href="{{ $link }}">{{ $link }}</a></p>
<p>هذا الرابط صالح لمدة 30 دقيقة</p>
</body>
</html>';
                
                file_put_contents($templatePath, $simpleTemplate);
                Log::info('[FORGOT PASSWORD] Created simple template');
            }

            // Attempt to send email
            Log::info('[FORGOT PASSWORD] Calling Mail::send');
            
            Mail::send('emails.password-reset', [
                'user' => $user,
                'link' => $resetLink,
                'appName' => 'مختصر الأرباح'
            ], function ($message) use ($user) {
                Log::info('[FORGOT PASSWORD] Building message', ['to' => $user->email]);
                $message->to($user->email)
                        ->subject('إعادة تعيين كلمة المرور - مختصر الأرباح');
            });

            Log::info('[FORGOT PASSWORD] Email sent successfully!', [
                'to' => $user->email,
                'user_id' => $user->id
            ]);

            return $this->addCorsHeaders(response()->json([
                'success' => true, 
                'message' => 'تم إرسال بريد إعادة تعيين كلمة المرور بنجاح'
            ]));

        } catch (\Swift_TransportException $e) {
            Log::error('[FORGOT PASSWORD] SMTP Transport Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'SMTP Error: ' . $e->getMessage(),
                'error_type' => 'smtp_transport'
            ], 500));
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[FORGOT PASSWORD] Validation Error', [
                'errors' => $e->errors()
            ]);
            
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422));
            
        } catch (\Throwable $e) {
            Log::error('[FORGOT PASSWORD] General Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ], 500));
        }
    }

    public function verifyResetToken(Request $request){
        try {
            $data = $request->validate(['email'=>'required|email', 'token'=>'required|string']);

            $user = User::where('email',$data['email'])
                ->where('password_reset_token',$data['token'])
                ->first();

            $valid = $user && $user->password_reset_expires_at && now()->lte($user->password_reset_expires_at);
            
            Log::info('[VERIFY RESET TOKEN]', [
                'email' => $data['email'],
                'valid' => $valid ? 'yes' : 'no'
            ]);
            
            return $this->addCorsHeaders(response()->json([
                'success'=>true,
                'valid'=>(bool)$valid
            ]));
            
        } catch (\Throwable $e) {
            Log::error('[VERIFY RESET TOKEN] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }

    public function resetPassword(Request $request){
        try {
            $data = $request->validate([
                'email'=>'required|email',
                'token'=>'required|string',
                'password'=>'required|string|min:' . $this->config['min_password_length'] . '|confirmed',
            ]);

            $user = User::where('email',$data['email'])
                ->where('password_reset_token',$data['token'])
                ->firstOrFail();

            if(!$user->password_reset_expires_at || now()->gt($user->password_reset_expires_at)){
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'message'=>'Token expired'
                ], 400));
            }

            $user->password = Hash::make($data['password']);
            $user->password_reset_token = null;
            $user->password_reset_expires_at = null;
            $user->save();

            Log::info('[RESET PASSWORD] Success', ['user_id' => $user->id]);
            
            return $this->addCorsHeaders(response()->json(['success'=>true]));

        } catch (\Throwable $e) {
            Log::error('[RESET PASSWORD] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }

    private function safeUser(User $u){
        return [
            'id'=>$u->id,
            'name'=>$u->name,
            'email'=>$u->email,
            'verified'=> !is_null($u->email_verified_at),
        ];
    }
}