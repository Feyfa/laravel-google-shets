<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Service_Sheets;
use Google_Client;
use GuzzleHttp\Client;

class SheetsController2 extends Controller
{
    private $service;

    public function __construct()
    {
        // Kosongkan konstruktor, inisialisasi akan dilakukan di metode lain
    }

    private function getGoogleClient($token = null)
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Integration');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        
        if ($token) {
            $client->setAccessToken($token);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
        } else {
            $client->setAuthConfig(storage_path('app/google_client_secret.json'));
        }

        return $client;
    }

    public function connectGoogleAccount()
    {
        $client = $this->getGoogleClient();
        $authUrl = $client->createAuthUrl();

        info([
            'authUrl' => $authUrl
        ]);

        return redirect($authUrl);
    }

    public function storeGoogleToken(Request $request)
    {
        info('start storeGoogleToken');

        $code = $request->code ?? "";
        if (empty($code) || trim($code) == '') {
            return redirect()->route('dashboard');
        }

        info('storeGoogleToken', ['code' => $code]);

        $client = $this->getGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        info('storeGoogleToken', ['client' => $client, 'token' => $token]);

        if (isset($token['error'])) {
            return response()->json(['error' => $token['error']], 400);
        }

        // Simpan token ke database user
        $user = User::where('id', 1)->first();
        $user->token = json_encode($token);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Google Account connected successfully!');
    }

    public function exportDataToGoogleSheets()
    {
        $user = User::where('id', 1)->first();

        if (!$user->token) {
            return response()->json(['error' => 'Google Account is not connected.'], 400);
        }

        $token = json_decode($user->token, true);
        $client = $this->getGoogleClient($token);
        $this->service = new Google_Service_Sheets($client);

        // Buat spreadsheet baru di akun Google guru
        $spreadsheet = new \Google_Service_Sheets_Spreadsheet();
        $spreadsheet->setProperties(new \Google_Service_Sheets_SpreadsheetProperties([
            'title' => 'Student Data ' . now()->format('Y-m-d H:i:s')
        ]));

        $spreadsheet = $this->service->spreadsheets->create($spreadsheet);

        // Ambil ID spreadsheet baru
        $spreadsheetId = $spreadsheet->spreadsheetId;

        // Data dummy untuk export
        $values = [
            ['ID', 'Name', 'Email', 'Address', 'Gender', 'Height', 'Weight'], // Header
            [1, 'John Doe', 'john.doe@example.com', '1234 Main St', 'Male', 170, 65],
            [2, 'Jane Smith', 'jane.smith@example.com', '5678 Oak Ave', 'Female', 160, 55],
        ];

        $body = new \Google_Service_Sheets_ValueRange(['values' => $values]);
        $params = ['valueInputOption' => 'RAW'];

        // Menambahkan data ke dalam spreadsheet
        $this->service->spreadsheets_values->update(
            $spreadsheetId,
            'Sheet1!A1', // Sheet dan rentang tempat data dimasukkan
            $body,
            $params
        );

        return response()->json(['spreadsheetId' => $spreadsheetId, 'message' => 'Spreadsheet created and data exported successfully!']);
    }

    public function revokeGoogleToken()
    {
        $user = User::where('id',1)->first();
    
        if (!$user->token) {
            return response()->json(['error' => 'No Google account connected.'], 400);
        }
    
        $token = json_decode($user->token, true);
        $refreshToken = $token['refresh_token'];
    
        // Buat instance Guzzle Client
        $client = new Client();
    
        // URL untuk mencabut token
        $url = 'https://oauth2.googleapis.com/revoke?token=' . $refreshToken;
    
        try {
            // Mengirim permintaan POST untuk mencabut token
            $response = $client->post($url);
    
            // Jika berhasil, hapus token dari database
            if ($response->getStatusCode() == 200) {
                $user->token = null;  // Hapus token dari database
                $user->save();
    
                return response()->json(['message' => 'Google account access revoked successfully!']);
            }
    
            return response()->json(['error' => 'Failed to revoke token.'], 400);
        } catch (\Exception $e) {
            // Menangani exception jika ada masalah dengan permintaan HTTP
            return response()->json(['error' => 'Error while revoking token: ' . $e->getMessage()], 500);
        }
    }

}
