<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class VerifyController extends Controller
{
    private $config = [
        'dev_mode' => false,
        'frontend_url' => 'http://localhost:3000',
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
            Log::info('[VERIFY CONFIG] Starting Gmail configuration');
            
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => $this->config['gmail']['username'],
                'password' => $this->config['gmail']['password'],
                'timeout' => 30,
            ]);
            Config::set('mail.from', [
                'address' => $this->config['gmail']['username'],
                'name' => $this->config['gmail']['from_name'],
            ]);
            app('mail.manager')->forgetMailers();
            
            Log::info('[VERIFY CONFIG] Gmail configured successfully');
            return true;
            
        } catch (\Throwable $e) {
            Log::error('[VERIFY CONFIG] Error: ' . $e->getMessage());
            return false;
        }
    }

    public function send(Request $request){
        Log::info('[VERIFY SEND] Started', ['email' => $request->email]);
        
        try {
            $data = $request->validate(['email'=>'required|email']);
            Log::info('[VERIFY SEND] Validation passed');

            $user = User::where('email',$data['email'])->first();
            
            if (!$user) {
                Log::error('[VERIFY SEND] User not found', ['email' => $data['email']]);
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404));
            }
            
            Log::info('[VERIFY SEND] User found', [
                'user_id' => $user->id,
                'verified' => $user->email_verified_at ? 'yes' : 'no'
            ]);

            if($user->email_verified_at) {
                Log::info('[VERIFY SEND] User already verified');
                return $this->addCorsHeaders(response()->json([
                    'success' => true,
                    'message' => 'Email already verified'
                ]));
            }

            $token = Str::random(64);
            $user->verification_token = $token;
            $user->save();
            
            Log::info('[VERIFY SEND] Token saved', ['token_length' => strlen($token)]);

            $verificationLink = $this->config['frontend_url'] . '/verify/confirm?email=' . urlencode($user->email) . '&token=' . $token;
            Log::info('[VERIFY SEND] Verification link created', ['link' => $verificationLink]);

            if ($this->config['dev_mode']) {
                Log::info('[VERIFY SEND] Dev mode - returning token');
                return $this->addCorsHeaders(response()->json([
                    'success' => true, 
                    'dev_token' => $token,
                    'verification_link' => $verificationLink,
                    'message' => 'DEV MODE: Use this token to verify email'
                ]));
            }

            Log::info('[VERIFY SEND] Production mode - sending email');
            
            $configSuccess = $this->configureGmail();
            if (!$configSuccess) {
                throw new \Exception('Failed to configure Gmail');
            }

            // Check template
            $templatePath = resource_path('views/emails/verify.blade.php');
            if (!file_exists($templatePath)) {
                Log::warning('[VERIFY SEND] Template not found, creating simple template');
                $templateDir = resource_path('views/emails');
                if (!is_dir($templateDir)) {
                    mkdir($templateDir, 0755, true);
                }
                
                $simpleTemplate = '<!DOCTYPE html>
<html>
<head><title>Email Verification</title></head>
<body>
<h2>مرحباً {{ $user->name }}</h2>
<p>انقر على الرابط التالي لتوثيق بريدك الإلكتروني:</p>
<p><a href="{{ $link }}">{{ $link }}</a></p>
</body>
</html>';
                
                file_put_contents($templatePath, $simpleTemplate);
            }

            Mail::send('emails.verify', [
                'user' => $user,
                'link' => $verificationLink,
                'appName' => 'مختصر الأرباح'
            ], function ($message) use ($user) {
                Log::info('[VERIFY SEND] Building message', ['to' => $user->email]);
                $message->to($user->email)
                        ->subject('توثيق البريد الإلكتروني - مختصر الأرباح');
            });

            Log::info('[VERIFY SEND] Email sent successfully!', [
                'to' => $user->email,
                'user_id' => $user->id
            ]);

            return $this->addCorsHeaders(response()->json([
                'success' => true, 
                'message' => 'تم إرسال بريد التوثيق بنجاح'
            ]));

        } catch (\Swift_TransportException $e) {
            Log::error('[VERIFY SEND] SMTP Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'SMTP Error: ' . $e->getMessage(),
                'error_type' => 'smtp'
            ], 500));
            
        } catch (\Throwable $e) {
            Log::error('[VERIFY SEND] Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ], 500));
        }
    }

    public function check(Request $request){
        try {
            $data = $request->validate(['email'=>'required|email']);
            $user = User::where('email',$data['email'])->first();
            
            Log::info('[VERIFY CHECK]', [
                'email' => $data['email'],
                'exists' => $user ? 'yes' : 'no',
                'verified' => $user && $user->email_verified_at ? 'yes' : 'no'
            ]);
            
            return $this->addCorsHeaders(response()->json([
                'success' => true, 
                'verified' => $user ? !is_null($user->email_verified_at) : false
            ]));
            
        } catch (\Throwable $e) {
            Log::error('[VERIFY CHECK] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }

    public function confirm(Request $request){
        Log::info('[VERIFY CONFIRM] Started', [
            'email' => $request->email,
            'token' => substr($request->token, 0, 10) . '...'
        ]);
        
        try {
            $data = $request->validate([
                'email'=>'required|email',
                'token'=>'required|string'
            ]);
            
            $user = User::where('email',$data['email'])
                ->where('verification_token',$data['token'])
                ->first();

            if (!$user) {
                Log::error('[VERIFY CONFIRM] User not found or invalid token');
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'message' => 'Invalid token or email'
                ], 404));
            }

            $user->email_verified_at = now();
            $user->verification_token = null;
            $user->save();

            Log::info('[VERIFY CONFIRM] Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $this->addCorsHeaders(response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ]));

        } catch (\Throwable $e) {
            Log::error('[VERIFY CONFIRM] Error', ['message' => $e->getMessage()]);
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500));
        }
    }
}