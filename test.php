<?php
require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');


$courses = get_current_course_id_number(false, $USER->id);
var_dump($courses);
echo ("\n\n");

$courseId = current($courses);
var_dump($courseId);
echo ("\n\n");

$campusCode = strtok($courseId, "~");
var_dump($campusCode);
echo ("\n\n");

//$partnershipCampusCodes = array("AW", "SH", "SW", "AL", "BR", "BW", "WT", "OCE", "SB", "DM", "GBB", "GBE", "GBL", "GBM", "GBW");

//if ($type == 'student' && !empty($campusCode) && in_array($campusCode, $partnershipCampusCodes)){
//    $pg_forms = false;
//    $ump_forms = false;
//}