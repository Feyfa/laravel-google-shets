<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheet;

class SheetsController3 extends Controller
{
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
            ['00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword','data1','data2','data3'],
            ['00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword','data1','',''],
            ['00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword','','data2',''],
            ['00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword','','','data3'],
            ['00000000',date('m/d/Y h:i:s A'),'John','Doe','johndoe1-@example.com','johndoe2-@example.com','123-123-1234','567-567-5678','John Doe Street','suite 101','Columbus','OH','43055','keyword']
        ];
        $clientGoogle->saveDataToSheet($content, $spreadSheetID);
    }
}
