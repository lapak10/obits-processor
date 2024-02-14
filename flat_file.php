<?php
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

require_once __DIR__.'/vendor/autoload.php';
$all_data = [];
function get_text_run_data($element){
    // echo '<hr>';
    $data = [
                'name'=>[],
                'flag'=>false,
                'text'=>[]
            ];
    // Iterate through Text elements within the TextRun
    foreach ($element->getElements() as $textElement) {

            

        if($textElement instanceof Text && trim($textElement->getText()) !== ''){

            if (str_contains(strtolower(trim($textElement->getText())), '/flag')) {
                $data['flag'] = true;
                // echo 'FLAG ' . $textElement->getText();
            }

            else if($textElement->getFontStyle()->isBold()){
                // $data['name'] = $textElement->getText();

                array_push($data['name'], trim($textElement->getText()));
                // echo 'NAME ' . $textElement->getText();
            }
            
            else{
                // $data['text'] = $textElement->getText();
                array_push($data['text'], trim($textElement->getText()));
                // echo 'TEXT ' . $textElement->getText();
            }

        }
    }

    return $data;
}

function filterAndTransformObjects($objects) {
    $containsNotice = function ($name) {
        foreach ($name as $nameString) {
            if (stripos($nameString, 'NOTICE') !== false) {
                return true;
            }
        }
        return false;
    };

    $result = [];
    $prevObj = null;
    foreach ($objects as $obj) {
        if ($prevObj !== null) {
            $prevHasNameNoText = !empty($prevObj['name']) && empty($prevObj['text']);
            $currHasTextNoName = !empty($obj['text']) && empty($obj['name']);

            if ($prevHasNameNoText && $currHasTextNoName) {
                // Merge adjacent objects
                $mergedName = implode(' ', array_map('trim', array_merge($prevObj['name'], $obj['name'])));
                $mergedText = implode(' ', array_map('trim', array_merge($prevObj['text'], $obj['text'])));
                $mergedObj = [
                    'name' => $mergedName,
                    'flag' => $prevObj['flag'] || $obj['flag'],
                    'text' => $mergedText
                ];
                // Replace the previous object with the merged one
                $result[count($result) - 1] = $mergedObj;
                $prevObj = null; // Skip adding the current object to the result
                continue;
            }
        }

        if (!$containsNotice($obj['name'])) {
            // Trim and merge name and text arrays into strings
            $nameString = implode(' ', array_map('trim', $obj['name']));
            $textString = implode(' ', array_map('trim', $obj['text']));
            $result[] = [
                'name' => $nameString,
                'flag' => $obj['flag'],
                'text' => $textString
            ];
        }

        $prevObj = $obj;
    }

    return $result;
}

function get_result_from_flat_file($phpWord){
    $all_data = [];
    
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if ($element instanceof TextRun){
                array_push($all_data, get_text_run_data($element));
            }
        }
    }

    return filterAndTransformObjects($all_data);
}

// function process_file($filePath,$dateArray){
//     $all_data = [];
//     $text = '';
//     $objReader = WordIOFactory::createReader('Word2007');
//     $phpWord = $objReader->load($filePath); // instance of \PhpOffice\PhpWord\PhpWord
//     foreach ($phpWord->getSections() as $section) {
        
//     foreach ($section->getElements() as $element) {
           
//            print(get_class($element).'<br/>');

//            if ($element instanceof TextRun) {
//                     array_push($all_data, get_text_run_data($element));
//                     // echo '<br/><br/><br/><br/>';
//             }
//         //    var_dump($result);
//         //    print_xml($result, $dateArray);
//         }
//     }

//     echo '<pre>' . var_export(filterAndTransformObjects($all_data), true) . '</pre>';
//     // echo json_encode($all_data);
// }
// $dateArray = [
//         'YEAR' => '2024',
//         'MONTH' => '02',
//         'DAY' => '14',
//     ];
// process_file('sample.docx', $dateArray);