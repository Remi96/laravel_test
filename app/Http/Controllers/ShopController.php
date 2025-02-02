<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use ZipArchive;

use Illuminate\Support\Facades\Log;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ShopController extends Controller
{
    /**
     * Display the shop view.
     */
    public function create(): Response
    {
        return Inertia::render('Shop/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $formattedName = str_replace(' ', '-', $request->name);

        $shop = Shop::create([
            'name' => $formattedName,
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $email = $user->email;
        $age = $user->age;

        $folder = storage_path('app/sites/' . $formattedName);
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        // Create index.html file for web site
        file_put_contents("{$folder}/index.html", "
            <html>
            <head><title>{$request->name}</title></head>
            <body>
                <h1>Boutique: {$request->name}</h1>
                <p>Email: {$email}</p>
                <p>Âge: {$age}</p>
            </body>
            </html>
        ");

        $token = 'nfp_cUffDV2h2f3LXXmrfSGB2WeNKKhAKq7T7734'; // Netlify token
        
        $createSiteResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post('https://api.netlify.com/api/v1/sites', [
            'name' => $formattedName,
        ]);

        if (!$createSiteResponse->successful()) {
            return response()->json([
                'error' => 'Erreur lors de la création du site.',
                'message' => $createSiteResponse->body(),
            ], 500);
        }

        $siteData = $createSiteResponse->json();
        $siteId = $siteData['id'];
        $siteUrl = $siteData['url'];

        Log::info($siteData);

        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder)) as $file) {
            if ($file->isDir()) continue;
        
            $filePath = $file->getPathname();
            $fileContent = file_get_contents($filePath);
            $fileSha = sha1($fileContent);
            $relativePath = str_replace($folder . '/', '', $filePath);
        
            $files[$relativePath] = $fileSha;
        }

        Log::info("Files list: ");
        Log::info(json_encode($files));

        // Init deployment
        $deployResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("https://api.netlify.com/api/v1/sites/{$siteId}/deploys", [
            'title' => "Mon deploiement " . $formattedName,
            'files' => $files,
        ]);

        Log::info($deployResponse->json());
        
        if (!$deployResponse->successful()) {
            return response()->json(['error' => 'Échec de la création du déploiement.'], 500);
        }
        
        $deployId = $deployResponse->json()['id'];

        Log::info("Deploy id: ");
        Log::info($deployId);


            $fileContent = file_get_contents($folder ."/index.html");
            Log::info($filePath);
            Log::info("Contenu du fichier: " . substr($fileContent, 0, 100));
        
            $response = Http::withHeaders([
                'accept-encoding' => 'gzip, deflate, br',
                'Accept'=> '*/*',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/octet-stream',
            ])->withBody($fileContent, 'application/octet-stream')
            ->put("https://api.netlify.com/api/v1/deploys/$deployId/files/index.html");
        
            Log::info($response->json());
        
        return response()->json([
            'message' => 'Site déployé avec succès !',
            'site_url' => "https://".$formattedName. ".netlify.app",
        ]);
        
    }
}
