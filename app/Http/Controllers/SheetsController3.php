<?php

namespace App\Http\Controllers;

use App\Models\CampaignInformation;
use Illuminate\Http\Request;
use App\Services\GoogleSheet;

class SheetsController3 extends Controller
{
    // name spreadsheet "jedun home 10:08"

    public function create()
    {
        $spreadsheetTitle = 'jedun home 10:08';
        $clientGoogle = new GoogleSheet();

        $spreadSheetID = $clientGoogle->createSpreadSheet($spreadsheetTitle);
        $clientGoogle->updateSheetTitle(date('Y'));
        $clientGoogle->setSheetName(date('Y'));

        $contentHeader[] = array('ID','ClickDate','First Name','Last Name','Email1','Email2','Phone1','Phone2','Address1','Address2','City','State','Zipcode','Keyword');
        $savedData = $clientGoogle->saveDataHeaderToSheet($contentHeader);

        $content[] = array('00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword');
        $savedData = $clientGoogle->saveDataToSheet($content);

        info(['spreadSheetID' => $spreadSheetID]);

        return response()->json(['spreadSheetID' => $spreadSheetID]);
    }

    public function addHeader()
    {
        $spreadSheetID = "1_98lnHbpC1DM7oKj6Oxq7iqmhDjBZanceQ0l2TE9NNY";
        $clientGoogle = new GoogleSheet();

        $clientGoogle->addHeader($spreadSheetID, ['merge1', 'merge2', 'merge3']);
    }

    public function addData()
    {
        $spreadSheetID = "1_98lnHbpC1DM7oKj6Oxq7iqmhDjBZanceQ0l2TE9NNY";
        $clientGoogle = new GoogleSheet();

        $content = [
            ['00000000',date('m/d/Y h:i:s A'),'Baim','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword','data1','data2','data3'],
        ];
        $promise = $clientGoogle->saveDataToSheet($content, $spreadSheetID);

        info('SYSTEM.OUT.PRINTLN(0)');
        sleep(1);
        info('SYSTEM.OUT.PRINTLN(1)');
        sleep(1);
        info('SYSTEM.OUT.PRINTLN(2)');
        sleep(1);
        info('SYSTEM.OUT.PRINTLN(3)');
        sleep(5);

        $promise->wait();
    }

    public function jidantest()
    {
        info("jidan test");

        $campaignAdvanceInformation = [2,4,5,6,7,8,9,10];

        $advanceInformation = [];
        $campaignInformation = CampaignInformation::where('status','active')->get();
        foreach($campaignInformation as $item)
        {
            $advanceInformation = array_merge($advanceInformation, json_decode($item->description, true));
        }

        $spreadSheetID = "1_98lnHbpC1DM7oKj6Oxq7iqmhDjBZanceQ0l2TE9NNY";
        $clientGoogle = new GoogleSheet();

        $sheetName = $clientGoogle->getSheetName($spreadSheetID);
        $sheetID = $clientGoogle->getSheetID($spreadSheetID, $sheetName);
        $oldHeader = $clientGoogle->getHeader($spreadSheetID);

        // cek apakah header nya belum ada sama sekali
        $headerAdvanceInformationExists = false;
        foreach($oldHeader as $item)
        {
            if(in_array($item, $advanceInformation)) {
                $headerAdvanceInformationExists = true;
            }
        }
        // cek apakah header nya belum ada sama sekali

        if(!$headerAdvanceInformationExists)
        {
            info('masuk untuk add header');
            $result = $clientGoogle->addHeader($spreadSheetID, $advanceInformation);

            // sembunyian colum yang tidak di uncheck
            $campaignInformationNotInId = CampaignInformation::whereNotIn('id', $campaignAdvanceInformation)
                                                             ->where('status', 'active')
                                                             ->get();

            foreach($campaignInformationNotInId as $item)
            {
                info('jalankan function showhideColumn');
                $startIndex = intval($item->start_index);
                $endIndex = intval($item->end_index + 1);
                $clientGoogle->showhideColumn($spreadSheetID, $sheetID, $startIndex,$endIndex, 'T');
            }
            // sembunyian colum yang tidak di uncheck

            return response()->json([
                'result' => $result
            ]);
        }
        else 
        {
            info('masuk untuk sisipkan column');
            $newHeader = [
                "ID",
                "ClickDate",
                "First Name",
                "Last Name",
                "Email1",
                "Email2",
                "Phone1",
                "Phone2",
                "Address1",
                "Address2",
                "City",
                "State",
                "Zipcode",
                "Keyword"
            ];
            $newHeader = array_merge($newHeader, $advanceInformation);
    
            $different = array_diff($newHeader, $oldHeader);
            $indexOfDifferent = array_keys($different);

            info([
                'indexOfDifferent' => $indexOfDifferent
            ]);
    
            if(count($indexOfDifferent) > 0) 
            {
                info('jalankan function insertColumnInSheet');
                foreach($indexOfDifferent as $item)
                {
                    $clientGoogle->insertColumnInSheet($spreadSheetID, $sheetID, $item);
                }
                
                info('jalankan function updateHeader');
                $clientGoogle->updateHeader($spreadSheetID, $newHeader);

                // sembunyian colum yang tidak di uncheck
                $campaignInformationNotInId = CampaignInformation::whereNotIn('id', $campaignAdvanceInformation)
                                                                 ->where('status', 'active')
                                                                 ->get();

                foreach($campaignInformationNotInId as $item)
                {
                    info('jalankan function showhideColumn');
                    $startIndex = intval($item->start_index);
                    $endIndex = intval($item->end_index + 1);
                    $clientGoogle->showhideColumn($spreadSheetID, $sheetID, $startIndex,$endIndex, 'T');
                }
                // sembunyian colum yang tidak di uncheck
            }
    
            
            return response()->json([
                'oldHeader' => $oldHeader,
                'newHeader' => $newHeader,
                'different' => $different,
                'indexOfDifferent' => $indexOfDifferent
            ]);
        }

        return response()->json(['message'=>'success','headerAdvanceInformationExists' => $headerAdvanceInformationExists]);
    }
}