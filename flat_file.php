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
    // Recursive call for nested table
    // $str =$element->getText();
    // $str = str_replace(array("\n", "\r"), '#', $element->getText());
    echo '<hr>';
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
                echo 'FLAG ' . $textElement->getText();
            }

            else if($textElement->getFontStyle()->isBold()){
                // $data['name'] = $textElement->getText();

                array_push($data['name'], trim($textElement->getText()));
                echo 'NAME ' . $textElement->getText();
            }
            
            else{
                // $data['text'] = $textElement->getText();
                array_push($data['text'], trim($textElement->getText()));
                echo 'TEXT ' . $textElement->getText();
            }

            // array_push($all_data, $data);


            // $name = ''.$textElement->getText();
            // echo $name.' ## '.$isFlagDetected.' ## '. $obituaryText;
            echo '<br/>';
        }

        


        // Check if the Text is bold
        // if ($textElement instanceof Text && $textElement->getFontStyle()->isBold()) {
        //     $name .= ''.$textElement->getText();
        //      echo '<br/>';
        //     // echo "" . $textElement->getText() . "";
        // } else {
        //     if (str_contains(strtolower(trim($textElement->getText())), '/flag')) {
        //         $isFlagDetected = true;
        //     }else{
        //         $obituaryText .= ' '.$textElement->getText();
        //         // echo 'Obituary Text->'.$textElement->getText();
        //     }
            
        // }
        // echo "<br/>";
    }

    // if(trim($name)!==''){
    //     $data['name'] = trim($name);
    //     echo 'Name-> '.$name;
    //     echo '<br/>';
        
    // }
    
    // if($isFlagDetected){
    //     $data['flag'] = true;
    //     echo 'FLAG DETECTED';
    //     echo '<br/>';
    // }

    // if(trim($obituaryText)!==''){
    //     $data['text'] = $obituaryText;
    //     echo 'Obituary Text-> '.$obituaryText;
    //     echo '<br/>';
    // }
    // echo json_encode($all_data);
    // return var_dump($all_data);
    return $data;
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
                    get_text_run_data($element);
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

    // print(get_class($element).'<br/>');

    if ($element instanceof Table) {
            // Call the recursive function for each table
            $result = printTextFromTable($element);

    }
    // and so on for other element types (see src/PhpWord/Element)

    return $result;
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

function process_file($filePath,$dateArray){
    $all_data = [];
    $text = '';
    $objReader = WordIOFactory::createReader('Word2007');
    $phpWord = $objReader->load($filePath); // instance of \PhpOffice\PhpWord\PhpWord
    foreach ($phpWord->getSections() as $section) {
        
    foreach ($section->getElements() as $element) {
           
           print(get_class($element).'<br/>');

           if ($element instanceof TextRun) {
                    array_push($all_data, get_text_run_data($element));
                    // echo '<br/><br/><br/><br/>';
            }
        //    var_dump($result);
        //    print_xml($result, $dateArray);
        }
    }

    echo '<pre>' . var_export(filterAndTransformObjects($all_data), true) . '</pre>';
    // echo json_encode($all_data);
}
$dateArray = [
        'YEAR' => '2024',
        'MONTH' => '02',
        'DAY' => '14',
    ];
process_file('sample.docx', $dateArray);