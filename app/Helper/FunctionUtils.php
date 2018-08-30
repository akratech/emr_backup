<?php

namespace App\Helper;

use Illuminate\Http\Request;
use URL;

class FunctionUtils {    

    public static function createDir($dirName) {

        if (!empty($dirName)) {
            $UpDir = FunctionUtils::getUploadPath();
            if (!is_dir($UpDir)) {
                mkdir($UpDir); //create the directory
                chmod($UpDir, 0777); //make it writable
            }
            $path = FunctionUtils::getUploadPath() . $dirName . "/";
            if (!is_dir($path)) {
                mkdir($path, 0777);
                chmod($path, 0777);
            }
        }
    }

    public static function base_url() {
        return url("/");
    }

    public static function getUploadUrl() {
        return url('uploads/');
    }

    public static function getProfileUploadUrl() {
        return url('uploads/profile') . '/';
    }

    public static function getUploadPath() {
        $path = public_path() . '/uploads/';
        if (!is_dir($path)) {            
            mkdir($path); //create the directory
            chmod($path, 0777); //make it writable
        }
        return $path;
    }

    public static function getProfileUploadPath() {
        return FunctionUtils::getUploadPath() . 'profile/';
    }    
   
    public static function UploadFile($Files, $DestPath, $extra = '') {
        $FileName = '';
        if ($Files->isValid()) {
            $extra = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
            $extension = $Files->getClientOriginalExtension(); // getting image extension
            $ValidExtensionArr = array("jpg", "jpeg", "png" , "flv" , "mp4" , "3gp", 'txt', 'doc', 'docx', 'pdf', 'zip', 'xlsx', 'xls', 'rtf');
            if (!empty($extra)) {
                $FileName = time() . "_" . $extra . '.' . $extension;
            } else {
                $FileName = time() . '.' . $extension;
            }
            //if (in_array(strtolower($extension), $ValidExtensionArr)) {
            $Files->move($DestPath, $FileName); // uploading file to given path
            return $FileName;
            /* } else {
              return flase;
              } */
        } else {
            return false;
        }
    }

    public static function getProfilePicture($profile_picture) {                
        if($profile_picture != '' && file_exists(public_path('uploads/profile/'.$profile_picture))) {
            $profile = URL::asset(FunctionUtils::getUploadUrl()."/profile/".$profile_picture);
        } else {            
            $profile = "";
        }
        return $profile;
    }

    public static function removeNullValue($data) {
        $data = json_decode(json_encode($data), true);
        array_walk_recursive($data, function (&$item, $key) {
            $item = null === $item ? '' : $item;
        });
        return $data;
    }
}
?>