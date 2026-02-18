<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UploadController extends Controller
{
    // Hardcoded Cloudinary configuration with upload preset only
    private $config = [
        'max_file_size' => 5120, // 5MB in KB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'use_cloudinary' => true,
        'cloudinary' => [
            'cloud_name' => 'dyb9rkpwj', // CHANGE THIS
            'upload_preset' => 'soufiane', // CHANGE THIS
            'folder' => 'earnings-app/products',
        ],
    ];

    public function image(Request $request){
        $request->validate([
            'image' => 'required|image|max:' . $this->config['max_file_size']
        ]);

        $file = $request->file('image');
        
        // Upload to Cloudinary using upload preset
        if ($this->config['use_cloudinary']) {
            return $this->uploadToCloudinary($file);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cloudinary is not enabled'
        ], 400);
    }

    private function uploadToCloudinary($file)
    {
        try {
            // Prepare the upload URL
            $url = "https://api.cloudinary.com/v1_1/" . $this->config['cloudinary']['cloud_name'] . "/image/upload";
            
            // Prepare the file for upload
            $fileContent = fopen($file->getRealPath(), 'r');
            
            // Send request to Cloudinary
            $response = Http::attach(
                'file', $fileContent, $file->getClientOriginalName()
            )->post($url, [
                'upload_preset' => $this->config['cloudinary']['upload_preset'],
                'folder' => $this->config['cloudinary']['folder'],
                'public_id' => time() . '_' . uniqid(),
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                return response()->json([
                    'success' => true,
                    'url' => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'path' => $result['public_id'],
                    'storage' => 'cloudinary'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cloudinary upload failed: ' . $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudinary upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}