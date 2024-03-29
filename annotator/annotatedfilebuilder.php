<?php
/**
 * @author Asha Jose, Parvathy S
 * This page contains functions that annotates using fpdf
 * 
 * The data stored as suitable arrays by the parser.php file is utilized here.
 * Depending on the data, different objects can be drawn on top of a pdf using these functions.
 */
defined('MOODLE_INTERNAL') || die();

define("BRUSHSIZE",0.50);
define("FONTTYPE",'Times');
define("OPACITY",0.30);
define("FULLOPACITY",1);
define("FONTRATIO",1.6);
define("XOFFSET",1);
define("YOFFSET",1);
define("ADJUSTPAGESIZE",FALSE);

/**
 * Takes file to annotate and the annotation data passed from upload.php and
 * returns the annotated PDF File Object
 *
 * @param string $file the name of the file to annotate
 * @param object $json the annotation data
 * @return $pdf the fpdi object that has information related to pdf file and annotations.
 */
function build_annotated_file($file, $json)
{
    //Get the page orientation
    $orientation = $json["page_setup"]['orientation'];
    $orientation = ($orientation=="portrait")? 'p' : 'l';

    //FPDI class defined in alphapdf.php
    $pdf = new AlphaPDF($orientation); 
    $pagecount = $pdf->setSourceFile($file);
    //Take the pages of PDF one-by-one and annotate them
    for($i=1 ; $i <= $pagecount; $i++)
    {
        //Functions from FPDI
        $template = $pdf->importPage($i); 
        $size = $pdf->getTemplateSize($template); 
        $pdf->addPage(); 
        $pdf->useTemplate($template, XOFFSET, YOFFSET, $size['width'], $size['height'], ADJUSTPAGESIZE); 
        $currPage = $json["pages"][$i-1];

        if(count((array)$currPage) == 0) //To check whether the current page has no annotations
            continue;
        //Number of objects in the current page
        $objnum = count((array)$currPage[0]["objects"]);

        for($j = 0; $j < $objnum; $j++)
        {
            $arr = $currPage[0]["objects"][$j];
            if($arr["type"]=="path")
            {
                draw_path($arr,$pdf);
            }
            else if($arr["type"]=="i-text")
            {
                insert_text($arr,$pdf);
            }
            else if($arr["type"]=="rect")
            {
                draw_rect($arr,$pdf);
            }
        }
    } 

    return $pdf;
}

/**
 * Function to draw free hand drawing
 * Given the array containing information related to path containg the FPDF line object and 
 * FPDI file object, it adds the path as series of line object to FPDI file object
 *
 * @param array $arr the deserialized data array for the path in FPDF line format
 * @param object $pdf the fpdi object that has information related to pdf file and annotations.
 */
function draw_path($arr, $pdf) 
{
    $list = parser_path($arr);
    $stroke = process_color(end($list));
    $pdf->SetDrawColor($stroke[0], $stroke[1], $stroke[2]);   // r g b of stroke color
    $pdf->SetLineWidth(BRUSHSIZE);
    for($k = 0; $k < sizeof($list) - 2; $k++) {
        $pdf->Line($list[$k][0],                      // x1
        $list[$k][1],                                 // y1
        $list[$k + 1][0],                             // x2
        $list[$k + 1][1]);                            // y2
    } 
}

/**
 * Function to insert text
 * Given the array containing information related to FPDF text object and FPDI file object,
 * it adds the text object to FPDI file object
 *
 * @param array $arr the deserialized data array in FPDF text format
 * @param object $pdf the fpdi object that has information related to pdf file and annotations.
 */
function insert_text($arr,$pdf)
{
    $list = parser_text($arr);
    $color = process_color($list[5]);
    $pdf->SetTextColor($color[0], $color[1], $color[2]);       // r g b
    $pdf->SetFont(FONTTYPE);                                  
    // converting fabricjs font size to that of fpdf
    $pdf->SetFontSize($list[6]/FONTRATIO);                                         
    $pdf->text($list[0],                                       // x
    $list[1] + $list[3],                                       // y  ( base + height)
    $list[4]);                                                 // text content
}

/**
 * Function to draw a rectangle
 * Given the array containing information related to FPDF Rect object and FPDI file object,
 * it adds the Rect object to FPDI file object
 *
 * @param array $arr the deserialized data array in FPDF Rect format
 * @param object $pdf the fpdi object that has information related to pdf file and annotations.
 */
function draw_rect($arr,$pdf)
{
    $list = parser_rectangle($arr);
    $fill = process_color($list[4]);
    $pdf->SetFillColor($fill[0],$fill[1],$fill[2]);              // r g b
    $pdf->SetAlpha(OPACITY);                  // for highlighting
    $pdf->Rect($list[0],                      // x
    $list[1],                                 // y
    $list[2],                                 // width
    $list[3],'F');                            // height
    // F refers to syle fill
    $pdf->SetAlpha(FULLOPACITY);              // setting the opacity back to 1.
}
?>