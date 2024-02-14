<?php
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

require_once __DIR__.'/vendor/autoload.php';

$text = '';

// Function to validate XML against XSD
function validateXml($xmlString) {
    
libxml_use_internal_errors(TRUE);

    $dom = new DOMDocument();
    $dom->loadXML($xmlString);
    // var_dump(libxml_get_errors());
    return $dom->validate();
}

// Recursive function to print text from table elements
function printTextFromTable($table)
{   $all_data = [];
    foreach ($table->getRows() as $row) {
        foreach ($row->getCells() as $cell) {
            $data = [
                'name'=>'',
                'flag'=>false,
                'text'=>''
            ];
            //echo 'START OF THE CELL<hr/>';
            foreach ($cell->getElements() as $element) {
                // print('inside table'.get_class($element).'<br />');
                if ($element instanceof Text) {
                    return $element->getText() . PHP_EOL;
                } elseif ($element instanceof Table) {
                    // Recursive call for nested table
                    printTextFromTable($element);
                } elseif ($element instanceof TextRun) {
                    // Recursive call for nested table
                    // $str =$element->getText();
                    // $str = str_replace(array("\n", "\r"), '#', $element->getText());
                    $name = '';
                    $isFlagDetected = false;
                    $obituaryText = '';
                    // Iterate through Text elements within the TextRun
                    foreach ($element->getElements() as $textElement) {
                        // Check if the Text is bold
                        if ($textElement instanceof Text && $textElement->getFontStyle()->isBold()) {
                            $name .= ''.$textElement->getText();
                            // echo "" . $textElement->getText() . "";
                        } else {
                            if (str_contains(strtolower(trim($textElement->getText())), '/flag')) {
                                $isFlagDetected = true;
                            }else{
                                $obituaryText .= ' '.$textElement->getText();
                                // echo 'Obituary Text->'.$textElement->getText();
                            }
                            
                        }
                        // echo "<br/>";
                    }

                    if(trim($name)!==''){
                        $data['name'] = trim($name);
                        //echo 'Name-> '.$name;
                    }
                    
                    if($isFlagDetected){
                        $data['flag'] = true;
                        //echo 'FLAG DETECTED';
                    }

                    if(trim($obituaryText)!==''){
                        $data['text'] = $obituaryText;
                        //echo 'Obituary Text-> '.$obituaryText;
                    }

                    // print($str.'<br/>');
                }
                elseif ($element instanceof TextBreak) {
                    // Recursive call for nested table
                    // print('TEXTBREAK<br/>');
                }
                //echo "<br/>";
            }
            // echo "<br/>";
            array_push($all_data, $data);
            //echo 'END OF THE CELL<hr/>';
        }
    }
    // var_dump($all_data);
    return $all_data;
}

function getWordText($element) {
    // print(get_class($element).'<br />');
    
    $result = [];

    if ($element instanceof Table) {
            // Call the recursive function for each table
            $result = printTextFromTable($element);

    }
    // and so on for other element types (see src/PhpWord/Element)

    return $result;
}

function get_pub_code_part($pub_code_data){
$template = '
<pub-code>staradvertiser.com
<ad-type>CLS Obits Liner
<cat-code>Obituaries</cat-code>
<class-code>CLS Obituaries</class-code>
<subclass-code>SUBCLASS_CODE</subclass-code>
<placement-description></placement-description>
<position-description>Funeral Notices</position-description>
<subclass3-code></subclass3-code>
<subclass4-code></subclass4-code>
<ad-number>AD_NUMBER</ad-number>
<start-date>RUN_DATE</start-date>
<end-date>RUN_DATE</end-date>
<line-count></line-count>
<run-count></run-count>
<customer-type></customer-type>
<account-number></account-number>
<account-name></account-name>
<addr-1></addr-1>
<addr-2></addr-2>
<block-house-number></block-house-number>
<unit-number></unit-number>
<floor-number></floor-number>
<pobox-number></pobox-number>
<attention-to></attention-to>
<city></city>
<state></state>
<postal-code></postal-code>
<country></country>
<phone-number></phone-number>
<fax-number></fax-number>
<url-addr></url-addr>
<email-addr></email-addr>

<pay-flag>N</pay-flag>

<ad-description></ad-description>
<order-source></order-source>
<order-status></order-status>
<payor-acct></payor-acct>
<agency-flag></agency-flag>
<rate-note></rate-note>
<edition></edition>
<zone></zone>

<Online_Product>staradvertiser.com</Online_Product>

<UserDate1Label></UserDate1Label>
<UserDate2Label></UserDate2Label>
<UserDate3Label></UserDate3Label>
<UserDate4Label></UserDate4Label>

<FieldedDataSet>
 <Name><![CDATA[NAME]]></Name>
 <Flag><![CDATA[FLAG]]></Flag>
 <Memoriam><![CDATA[False]]></Memoriam>
</FieldedDataSet>
<ad-content><![CDATA[ 	   NAME     TEXT

  ]]></ad-content>
</ad-type>
</pub-code>';

$replace_data_keys = [
    'RUN_DATE' => $pub_code_data['RUN_DATE'],
    'AD_NUMBER' =>$pub_code_data['AD_NUMBER'],
    'NAME' => $pub_code_data['NAME'],
    'TEXT' => $pub_code_data['TEXT'],
    'FLAG' => $pub_code_data['FLAG'],
    'SUBCLASS_CODE' => $pub_code_data['SUBCLASS_CODE']
];

foreach ($replace_data_keys as $key => $value) {
    $template = str_replace($key, $value, $template);
}

return $template;


}

function print_xml($all_data,$datepicker_obj){
    // remove incomplete data
    if(count($all_data) === 0){
        return;
    }
    $all_data = array_filter($all_data,function($data){ return  !empty(trim($data['name'])) && !empty(trim($data['text']));  } );
    $all_data = array_filter($all_data,function($data){ return count($data)>0;  } );
    
    // var_dump($all_data);


    $runDate = "{$datepicker_obj['MONTH']}/{$datepicker_obj['DAY']}/{$datepicker_obj['YEAR']}";
    $adNumberPrefix = "{$datepicker_obj['YEAR']}{$datepicker_obj['MONTH']}{$datepicker_obj['DAY']}";


    $xmlTemplate = '
<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<web-export>
<run-date>RUN_DATE
PUB_CODES
</run-date>
</web-export>';

    $xmlTemplate = str_replace('RUN_DATE', $runDate, $xmlTemplate);

    $pub_codes = '';

    $count = 0;
    foreach ($all_data as $key => $value) {
        $pub_code_data = [
            'RUN_DATE' => $runDate,
            'AD_NUMBER' =>$adNumberPrefix .($_POST['subclass'] === '4000'? '-':''). sprintf("%02d", (intval($_POST['adnumber']) + $count) ),
            'NAME' => $value['name'],
            'TEXT' => $value['text'],
            'FLAG' => $value['flag']?'True':'False',
            'SUBCLASS_CODE' => $_POST['subclass']
        ];

        $pub_codes .= get_pub_code_part($pub_code_data);
        $count = $count + 1;
    }

    $xmlTemplate = str_replace('PUB_CODES', $pub_codes, $xmlTemplate);
    
    echo htmlentities($xmlTemplate);



}

function is_file_grid_fashion($phpWord){
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
           if ($element instanceof Table) {
            return true;
           }
        }
    }
    return false;
}

function print_result_from_grid_file($phpWord, $dateArray){
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
           $result =  getWordText($element);
           print_xml($result, $dateArray);
        }
    }
}

function process_file($filePath,$dateArray){
    $text = '';
    echo '<pre id="xmlOutput" style="
        text-wrap: wrap;
        background: antiquewhite;
        padding: 5px;
        border: 2px solid;
        border-radius: 5px;
        border-color: #72aee6;
    ">';
    $objReader = WordIOFactory::createReader('Word2007');
    $phpWord = $objReader->load($filePath); // instance of \PhpOffice\PhpWord\PhpWord
    
    if(is_file_grid_fashion($phpWord)){
        print_result_from_grid_file($phpWord, $dateArray);
    }else{
        $result = get_result_from_flat_file($phpWord);

        // var_dump($result);
    
        print_xml($result, $dateArray);
    }

    echo '</pre>';
}