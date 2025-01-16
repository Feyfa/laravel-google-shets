<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Drive;
use Google_Service_Drive_Permission;

class GoogleSheet
{
    private $client;
    private $googleSheetService;
    private $accessToken;
    private $spreadSheetId;
    private $sheetId;

    public function __construct()
    {
        $authConf = storage_path('app/google_client_secret.json');

        $this->client = new Google_Client();
        $this->client->setApplicationName('Google SpreadSheet');
        $this->client->setAuthConfig($authConf);
        // $this->client->setState($CompanyID);
        // $this->client->setRedirectUri(config("services.googleSpreadSheet.redirect"));
        $this->client->addScope("https://www.googleapis.com/auth/spreadsheets");
        $this->client->setAccessType('offline');  
        $this->client->setIncludeGrantedScopes(true); 
        
        $this->googleSheetService = new Google_Service_Sheets($this->client);

        /** CHECK FOR CLIENT TOKEN */
        $chkSetting = $this->get_setting();

        if(count($chkSetting) == 0) 
        {
            $auth_url = $this->client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            exit;die(); 
        }
        else if ($chkSetting != '')
        {
            $this->accessToken = $chkSetting;
            $this->client->setAccessToken($this->accessToken);
           
            if ($this->client->isAccessTokenExpired()) 
            {
                if($this->client->getRefreshToken()) 
                {
                    $this->accessToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    $this->client->setAccessToken($this->accessToken);

                    /* UPDATE ACCESS TOKEN */
                    $path = 'access_token_spreadsheet.json';
                    if(Storage::exists($path))
                    {
                        Storage::put($path, json_encode($this->accessToken));
                    }
                    /* UPDATE ACCESS TOKEN */
                }
            }
        }
    }

    private function get_setting() 
    {
        $path = 'access_token_spreadsheet.json';
        $access_token = [];

        if(Storage::exists($path))
        {
            $data = Storage::get($path);
            $access_token = json_decode($data, true);
        }

        return $access_token;
    }

    public function createSpreadSheet($title) 
    {
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);
        $spreadsheet = $this->googleSheetService->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
        $this->spreadSheetId = $spreadsheet->spreadsheetId;

        return $spreadsheet->spreadsheetId;
    }

    public function updateSheetTitle($title,$sheetID=0) 
    {
        $requests = [
            new Google_Service_Sheets_Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $sheetID,
                        'title' => $title,
                    ],
                    'fields' => 'title'
                ]
            ])
        ];

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $response = $this->googleSheetService->spreadsheets->batchUpdate($this->spreadSheetId, $batchUpdateRequest);    
    }

    public function setSheetName($sheetid) 
    {
        $this->sheetId = $sheetid . '!';
    }

    public function saveDataHeaderToSheet(array $data) 
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED',
        ];

        $range = $this->sheetId . "A1";

        return $this->googleSheetService
            ->spreadsheets_values
            ->update($this->spreadSheetId, $range, $body, $params);

    }

    public function saveDataToSheet(array $data, $spreadSheetId = "")
    {
        if(!empty($spreadSheetId) && trim($spreadSheetId) != "") {
            $this->spreadSheetId = $spreadSheetId;
        }
        
        $dimensions = $this->getDimensions($this->spreadSheetId);

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'RAW',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        $range = "A" . ($dimensions['rowCount'] + 1);

        return $this->googleSheetService
            ->spreadsheets_values
            ->append($this->spreadSheetId, $range, $body, $params);
    }

    private function getDimensions($spreadSheetId)
    {
        $rowDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => $this->sheetId . 'A:A', 'majorDimension' => 'COLUMNS']
        );

        //if data is present at nth row, it will return array till nth row
        //if all column values are empty, it returns null
        $rowMeta = $rowDimensions->getValueRanges()[0]->values;
        if (!$rowMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        $colDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => $this->sheetId . '1:1', 'majorDimension' => 'ROWS']
        );

        //if data is present at nth col, it will return array till nth col
        //if all column values are empty, it returns null
        $colMeta = $colDimensions->getValueRanges()[0]->values;
        if (!$colMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        return [
            'error' => false,
            'rowCount' => count($rowMeta[0]),
            'colCount' => $this->colLengthToColumnAddress(count($colMeta[0]))
        ];
    }

    private function colLengthToColumnAddress($number)
    {
        if ($number <= 0) return null;

        $letter = '';
        while ($number > 0) {
            $temp = ($number - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $number = ($number - $temp - 1) / 26;
        }
        return $letter;
    }

    public function getHeader($spreadsheetId)
    {   
        $sheetNames = $this->getSheetName($spreadsheetId);
        
        /* GET SPREADSHEET DATA */
        $range = "{$sheetNames}!1:1";
        $response = $this->googleSheetService->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues()[0];
        info('', ['values' => $values]);
        /* GET SPREADSHEET DATA */

        return $values;
    }

    public function getSheetName($spreadsheetId)
    {
        /* GET SHEET NAMES */
        // Mendapatkan metadata spreadsheet
        $spreadsheet = $this->googleSheetService->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();
        info('', ['sheets' => $sheets]);
        // Mengambil semua nama sheet dari metadata
        
        $sheetNames = [];
        foreach ($sheets as $sheet) 
        {
            $sheetNames[] = $sheet->properties->title;
        }
        info('', ['sheetNames' => $sheetNames]);

        $firstSheetName = $sheetNames[0] ?? "";
        /* GET SHEET NAMES */

        return $firstSheetName;
    }

    public function insertColumnInSheet($spreadsheetId, $sheetID, $columnIndex)
    {
        // Menyisipkan kolom baru di posisi tertentu
        $requests = [
            new Google_Service_Sheets_Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $sheetID, // Sheet ID bisa didapatkan lewat Google Sheets API atau UI
                        'dimension' => 'COLUMNS',
                        'startIndex' => $columnIndex, // Indeks tempat kolom baru akan disisipkan
                        'endIndex' => $columnIndex + 1, // Menyisipkan satu kolom baru
                    ],
                    'inheritFromBefore' => false, // Tidak mewarisi format dari kolom sebelumnya
                ]
            ])
        ];

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        try {
            $response = $this->googleSheetService->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
            info("Kolom baru berhasil disisipkan");
        } catch (\Exception $e) {
            info("Error: " . $e->getMessage());
        }
    }

    public function updateHeader($spreadsheetId, $headerData)
    {
        $sheetName = $this->getSheetName($spreadsheetId);

        // Memperbarui header setelah kolom baru ditambahkan
        $range = "$sheetName!A1";  // Baris pertama (header)
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$headerData], // Data header yang diperbarui
        ]);

        try {
            $this->googleSheetService->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            );
            info("Header berhasil diperbarui!\n");
        } catch (\Exception $e) {
            info("Error: " . $e->getMessage());
        }
    }

    public function addHeader($spreadsheetId, $newHeader = [])
    {
        // /* GET SHEET NAMES */
        // // Mendapatkan metadata spreadsheet
        // $spreadsheet = $this->googleSheetService->spreadsheets->get($spreadsheetId);
        // $sheets = $spreadsheet->getSheets();
        // info('', ['sheets' => $sheets]);
        // // Mengambil semua nama sheet dari metadata
        
        // $sheetNames = [];
        // foreach ($sheets as $sheet) 
        // {
        //     $sheetNames[] = $sheet->properties->title;
        // }
        // info('', ['sheetNames' => $sheetNames]);
        // /* GET SHEET NAMES */

        
        /* GET SPREADSHEET DATA */
        // $firstSheetName = $sheetNames[0] ?? "";

        $sheetName = $this->getSheetName($spreadsheetId);
        $range = "{$sheetName}!1:1";

        $response = $this->googleSheetService->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        info('', ['values' => $values]);
        /* GET SPREADSHEET DATA */

        /* ADD NEW HEADER */
        $currentHeaders = [];
        if (!empty($values) && !empty($newHeader))
        {
            /* GET HEADER THAT DOESN'T EXIST IN GOOGLE SHEET */
            $currentHeaders = $values[0];
            $newHeaderFilter = array_diff($newHeader, $currentHeaders);
            info('', ['newHeaderFilter' => $newHeaderFilter]);
            /* GET HEADER THAT DOESN'T EXIST IN GOOGLE SHEET */
            
            if(!empty($newHeaderFilter))
            {
                /* UPDATE HEADER GOOGLE SHEET */
                $mergeHeader = array_merge($currentHeaders, $newHeaderFilter);
                // Log::info('', ['newHeaders' => $mergeHeader]);
    
                $valueRange = new \Google_Service_Sheets_ValueRange([
                    'values' => [$mergeHeader], // Header baru di array
                ]);
                $options = ['valueInputOption' => 'RAW'];

                $response = $this->googleSheetService->spreadsheets_values->update($spreadsheetId, $range, $valueRange, $options);
                info('', ['response' => $response]);
                /* UPDATE HEADER GOOGLE SHEET */

                return true;
            }
        }
        /* ADD NEW HEADER */

        return false;
    }

    public function getSheetID($spreadSheetId, $sheetName)
    {
        $response = $this->googleSheetService->spreadsheets->get($spreadSheetId); 
        foreach($response['sheets'] as $rs) {
            if ($rs['properties']['title'] == $sheetName) {
                return $rs['properties']['sheetId'];
                break;
            }
        }
        return '';
    }

    public function showhideColumn($spreadSheetId,$sheetId,$colStartIndex,$colEndIndex,$hide = false) 
    {
        $requests = [
            new Google_Service_Sheets_Request([
                'updateDimensionProperties' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        "dimension" => 'COLUMNS',
                        "startIndex" => $colStartIndex,
                        "endIndex" => $colEndIndex,
                    ],
                    'properties' => [
                        "hiddenByUser" => $hide,
                    ],
                    "fields" => 'hiddenByUser',
                ]
            ])
        ];

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $response = $this->googleSheetService->spreadsheets->batchUpdate($spreadSheetId, $batchUpdateRequest); 
    }
}