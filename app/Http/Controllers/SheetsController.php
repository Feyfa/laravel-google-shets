<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Illuminate\Http\Request;

class SheetsController extends Controller
{
    private $service;

    public function __construct()
    {
        $auth = env('SHEETS_AUTH');
        $auth = json_decode($auth, true);

        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig($auth);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $this->service = new Google_Service_Sheets($client);
    }

    /**
     * READ SHEETS
     */
    public function read(Request $request)
    {
        $spreadsheetsId = '1WsYIWV-chFQfJlKwLyBxZJ_jqj2Gh90yYYu5oNbMZGk';
        $range = '2020!A2:A5';
        $response = $this->service->spreadsheets_values->get($spreadsheetsId, $range);
        $values = $response->getValues();

        return response()->json([
            'values' => $values
        ]);
    }

    /**
     * UPDATE ROWS SHEETS
     */
    public function update(Request $request)
    {
        $spreadsheetsId = '1WsYIWV-chFQfJlKwLyBxZJ_jqj2Gh90yYYu5oNbMZGk';
        $range = '2020!A2:C2';
        $values = [
            [
                '1','Jidan','jidan@gmail.com'
            ]
        ];
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $result = $this->service->spreadsheets_values->update($spreadsheetsId, $range, $body, $params);

        return response()->json([
            'result' => $result,
            'cek' => Google_Service_Sheets::SPREADSHEETS_READONLY
        ]);
    }

    /**
     * CREATE ROWS SHEETS
     */
    public function create(Request $request)
    {
        $spreadsheetsId = '1WsYIWV-chFQfJlKwLyBxZJ_jqj2Gh90yYYu5oNbMZGk';
        $range = '2020';
        $values = [
            [
                '8','Ariel','ariel@gmail.com','Jalan Jati Pulo','Laki Laki', '185', '90'
            ]
        ];
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $insert = [
            'insertDataOption' => 'INSERT_ROWS'
        ];
        $result = $this->service->spreadsheets_values->append($spreadsheetsId, $range, $body, $params, $insert);

        return response()->json([
            'result' => $result
        ]);
    }

    /**
     * DELETE ROWS SHEETS
     */
    public function delete(Request $request)
    {
        $spreadsheetsId = '1WsYIWV-chFQfJlKwLyBxZJ_jqj2Gh90yYYu5oNbMZGk';
        $gid = 0;
        $rowtodelete = 4;

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [
                [
                    'deleteDimension' => [
                        'range' => [
                            'sheetId' => $gid,
                            'dimension' => "ROWS",
                            'startIndex' => $rowtodelete,
                            'endIndex' => $rowtodelete + 1
                        ]
                    ]
                ]
            ]
        ]);

        $result = $this->service->spreadsheets->batchUpdate($spreadsheetsId, $batchUpdateRequest);

        return response()->json([
            'result' => $result,
            'batchUpdateRequest' => $batchUpdateRequest
        ]);
    }
}
