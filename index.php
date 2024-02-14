<?php
/*
Plugin Name: Obits Processor v2
Description: Converts an Obituary Docx file to simple text (Link available inside Tools > OPI Doc to Text)
Version: 1.1
Author: Anand
*/

function xml_validation_inprogress_notice() {
	$class = 'notice notice-info inprogress-xml-notice d-none';
	$message = __( 'Validating XML', 'sample-text-domain' );

	printf( '<div class="%1$s"><p><span class="dashicons dashicons-update spin" style="color:#72aee6;"></span> %2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
function xml_validation_ok_notice() {
	$class = 'notice notice-success valid-xml-notice d-none';
	$message = __( 'XML is Valid', 'sample-text-domain' );

	printf( '<div class="%1$s"><p><span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span> %2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
function xml_validation_error_notice() {
	$class = 'notice notice-error invalid-xml-notice d-none';
	$message = __( 'Invalid XML', 'sample-text-domain' );

	printf( '<div class="%1$s"><p><span class="dashicons dashicons-warning" style="color:#d63638;"></span> %2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
if (!function_exists('process_file'))
{
    require_once('custom_functions.php');
}

if (!function_exists('get_result_from_flat_file'))
{
    require_once('flat_file.php');
}


// add_action( 'admin_notices', 'xml_validation_ok_notice' );
// Add a subpage to the Tools menu
function opi_doc_to_text_menu() {
    add_submenu_page(
        'tools.php',            // Parent menu slug
        'Obits Processor',      // Page title
        'Obits Processor',      // Menu title
        'manage_options',       // Capability required to access the page
        'opi_doc_to_text_page', // Menu slug
        'opi_doc_to_text_page'  // Callback function to display the page content
    );
}
add_action('admin_menu', 'opi_doc_to_text_menu');

// Callback function to display the page content
function opi_doc_to_text_page() {

    if (isset($_POST['adnumber'])) {
        update_option('opi_doc_text_ad_number', intval($_POST['adnumber']) + 1);
    }
    ?>
    <style>
        .d-none{
            display:none;
        }
        .dashicons.spin {
       animation: dashicons-spin 1.2s infinite;
       animation-timing-function: linear;
    }

    @keyframes dashicons-spin {
       0% {
          transform: rotate( 0deg );
       }
       100% {
          transform: rotate( 360deg );
       }
    }
    </style>
    <script>
        function run_validation(){
            const xmlselector = 'pre#xmlOutput';
            if(jQuery(xmlselector).length){
                const brRegex = /<br\s*[\/]?>/gi;
                const XMLTEXT = jQuery(xmlselector).text().replace(brRegex, "\r\n");
                jQuery('.notice.inprogress-xml-notice').removeClass('d-none');


                setTimeout(()=>{
                        if(isValidXML(XMLTEXT)){
                            jQuery('.notice').addClass('d-none');
                            jQuery('.notice.valid-xml-notice').removeClass('d-none');
                            jQuery(xmlselector).css('border-color','#00a32a');
                        }else{
                            jQuery('.notice').addClass('d-none');
                            jQuery('.notice.invalid-xml-notice').removeClass('d-none');
                            jQuery(xmlselector).css('border-color','#d63638');
                        }
                }, 1500);
                
            }else{
                console.log('NOT FOUND');
            }
        }
        function isValidXML(xmlString) {
          let parser = new DOMParser();
          let xmlDoc;

          try {
            xmlDoc = parser.parseFromString(xmlString, "text/xml");
            // Check if parsing resulted in errors
            let errors = xmlDoc.getElementsByTagName("parsererror");
            if (errors.length > 0) {
              // XML is invalid
              return false;
            } else {
              // XML is valid
              return true;
            }
          } catch (error) {
            // If an exception occurred during parsing
            console.error("Error parsing XML:", error);
            return false;
          }
        }
        function copyToClipboard(element) {
          let $temp = jQuery("<textarea>");
          let brRegex = /<br\s*[\/]?>/gi;
          jQuery("body").append($temp);
          $temp.val(jQuery(element).text().replace(brRegex, "\r\n")).select();
          document.execCommand("copy");
          $temp.remove();
          alert('XML Copied to Clipboard!');
        }
        function save(element, filename) {
            let brRegex = /<br\s*[\/]?>/gi;
            const data = jQuery(element).text().replace(brRegex, "\r\n");
            const blob = new Blob([data], {type: 'text/xml'});
            if(window.navigator.msSaveOrOpenBlob) {
                window.navigator.msSaveBlob(blob, filename);
            }
            else{
                const elem = window.document.createElement('a');
                elem.href = window.URL.createObjectURL(blob);
                elem.download = filename;        
                document.body.appendChild(elem);
                elem.click();        
                document.body.removeChild(elem);
            }
        }
    </script>
    <div class="wrap">
        <h1>Obits Processor</h1>
        <form method="post" enctype="multipart/form-data">
            <label for="file">Upload File:</label>
            <input type="file" name="file" id="file" accept=".doc, .docx" required style="
    width: 200px;
">

            <label for="cars">Type:</label>

            <select name="subclass" style='width:100px;'>
              <option value="4001">Free</option>
              <option value="4000">Paid</option>
            </select>
            
            <label for="datepicker">Select Date:</label>
            <input type="date" name="datepicker" id="datepicker" class="datepicker" required>

            <label for="datepicker" style='margin-left:15px;'>Starting Ad-Number:</label>
            <input type="number" name="adnumber" min='1' max='1000' value='1' required>
            
            <input type="submit" name="submit" value="Upload" class="button button-primary">
            <?php 
            xml_validation_inprogress_notice();
            xml_validation_ok_notice();
            xml_validation_error_notice();
            if (isset($_POST['submit'])) { ?>
                <input type="button" name="copy" value="Copy XML to Clipboard" onclick="copyToClipboard('pre#xmlOutput')" class="button button-secondary">
                <input type="button" name="download" value="Download" download='Legacy-<?php echo $_POST['subclass'];?>.xml' onclick="save('pre#xmlOutput','Legacy-<?php echo $_POST['subclass'];?>.xml')" class="button button-secondary">
            <?php } ?>
            
        </form>

        <?php if (isset($_POST['submit'])) {
            
            do_action('admin_post_opi_doc_to_text_submit');
            
          };?>

          <script>
            run_validation();
          </script>
        
    </div>
    <?php
    
}

function get_date_object($datepickerString){
    $datepicker_array = explode("-",$datepickerString);
    return [
        'YEAR' => $datepicker_array[0],
        'MONTH' => $datepicker_array[1],
        'DAY' => $datepicker_array[2],
    ];
}

// Handle form submission
function handle_form_submission() {
        // var_dump($_POST);
        // var_dump($_FILES);

        $datepicker_obj = get_date_object($_POST['datepicker']);

        $filePath = dirname(__FILE__).'/'.$_FILES['file']['name'];
        // $filePath = dirname(__FILE__).'/'.'sample.docx';

        move_uploaded_file( $_FILES['file']['tmp_name'], $filePath);
        process_file($filePath, $datepicker_obj);
        unlink($filePath);
}
add_action('admin_post_opi_doc_to_text_submit', 'handle_form_submission');
