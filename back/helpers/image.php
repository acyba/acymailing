<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class ImageHelper extends acymObject
{
    //Handle error messages
    var $error;
    //New Height the picture should be
    var $maxHeight;
    //New Width the picture should be
    var $maxWidth;
    //folder where all pictures should be stored
    var $destination;

    public function removePictures($text)
    {
        //Remove the picture... normal process
        $return = preg_replace('#< *img((?!content_main_image)[^>])*>#Ui', '', $text);
        //Now remove the jce_caption div if there is one...
        $return = preg_replace('#< *div[^>]*class="jce_caption"[^>]*>[^<]*(< *div[^>]*>[^<]*<\/div>)*[^<]*<\/div>#Ui', '', $return);

        return $return;
    }

    public function available()
    {
        if (!function_exists('gd_info')) {
            $this->error = 'The GD library is not installed.';

            return false;
        }
        if (!function_exists('getimagesize')) {
            $this->error = 'Cound not find getimagesize function';

            return false;
        }
        if (!function_exists('imagealphablending')) {
            $this->error = "Please make sure you're using GD 2.0.1 or later version";

            return false;
        }

        return true;
    }

    //This function will check all images from the input and return an output with the pictures transformed with the right size
    public function resizePictures($input)
    {
        $this->destination = ACYM_MEDIA.'resized'.DS;
        acym_createDir($this->destination);
        $content = acym_absoluteURL($input);

        //regex take all images:
        preg_match_all('#<img([^>]*)>#Ui', $content, $results);
        if (empty($results[1])) {
            return $input;
        }

        $replace = [];

        foreach ($results[1] as $onepicture) {
            //Check if there the "donotresize" element inside, we don't resize it.
            //It can be either in the class or in the filename itself
            if (strpos($onepicture, 'donotresize') !== false) {
                continue;
            }

            //Take the path
            if (!preg_match('#src="([^"]*)"#Ui', $onepicture, $path)) {
                continue;
            }
            $imageUrl = $path[1];

            //We are nice guys... sometimes users use www. or not... so we convert both, same thing for httpS or http
            $imageUrl = acym_internalUrlToPath($imageUrl);

            $newPicture = $this->generateThumbnail($imageUrl);

            //Maybe we don't need to resize anything...
            if (!$newPicture) {
                $newDimension = 'width:'.$this->maxWidth.'px;height:'.$this->maxHeight.'px;';
                if (strpos($onepicture, 'style="') !== false) {
                    $replace[$onepicture] = preg_replace('#style="([^"]*)"#Uis', 'style="'.$newDimension.'$1"', $onepicture);
                } else {
                    $replace[$onepicture] = ' style="'.$newDimension.'" '.$onepicture;
                }
                continue;
            }

            //Because the ACYM_ROOT may be empty, we only make sure to replace the first instance found.
            $newPicture['file'] = preg_replace('#^'.preg_quote(ACYM_ROOT, '#').'#i', ACYM_LIVE, $newPicture['file']);
            $newPicture['file'] = str_replace(DS, '/', $newPicture['file']);
            //replace the image url now with the new one...
            $replaceImage = [];
            $replaceImage[$path[1]] = $newPicture['file'];
            //replace information if we had some (height,width...)
            if (preg_match_all('#(width|height)(:|=) *"?([0-9]+)#i', $onepicture, $resultsSize)) {
                foreach ($resultsSize[0] as $i => $oneArg) {
                    $newVal = (strtolower($resultsSize[1][$i]) == 'width') ? $newPicture['width'] : $newPicture['height'];
                    if ($newVal > $resultsSize[3][$i]) {
                        continue;
                    }
                    $replaceImage[$oneArg] = str_replace($resultsSize[3][$i], $newVal, $oneArg);
                }
            }

            $replace[$onepicture] = str_replace(array_keys($replaceImage), $replaceImage, $onepicture);
        }

        if (!empty($replace)) {
            $input = str_replace(array_keys($replace), $replace, $content);
        }

        return $input;
    }

    public function generateThumbnail($picturePath)
    {
        $paramsPos = strpos($picturePath, '?');
        if ($paramsPos !== false) $picturePath = substr($picturePath, 0, $paramsPos);

        list($currentwidth, $currentheight) = @getimagesize($picturePath);
        if (empty($currentwidth) || empty($currentheight)) {
            return false;
        }
        $factor = min($this->maxWidth / $currentwidth, $this->maxHeight / $currentheight);
        if ($factor >= 1) {
            return false;
        }
        $newWidth = round($currentwidth * $factor);
        $newHeight = round($currentheight * $factor);

        if (strpos($picturePath, 'http') === 0) {
            $filename = substr($picturePath, strrpos($picturePath, '/') + 1);
        } else {
            $filename = basename($picturePath);
        }

        if (substr($picturePath, 0, 10) == 'data:image') {
            //It's a picture in base64 encoding... we will apply an extension name and name based on the content...
            preg_match('#data:image/([^;]{1,5});#', $picturePath, $resultextension);
            if (empty($resultextension[1])) {
                return false;
            }
            $extension = $resultextension[1];
            $name = md5($picturePath);
        } else {
            $extension = strtolower(substr($filename, strrpos($filename, '.') + 1));
            $name = strtolower(substr($filename, 0, strrpos($filename, '.')));
            //We add the creation date of the file so if the file is modified, we will generate a new thumbnail
            //No need to use the full date, the last 4 characters will be enough!
            $name .= substr(@filemtime($picturePath), -4);
        }

        $newImage = md5($picturePath).'-'.$name.'thumb'.$this->maxWidth.'x'.$this->maxHeight.'.'.$extension;
        if (empty($this->destination)) {
            $newFile = dirname($picturePath).DS.$newImage;
        } else {
            $newFile = $this->destination.$newImage;
        }

        //The new file already exists, we don't have to create a new one
        if (file_exists($newFile)) {
            return ['file' => $newFile, 'width' => $newWidth, 'height' => $newHeight];
        }

        switch ($extension) {
            case 'gif':
                $img = ImageCreateFromGIF($picturePath);
                break;
            case 'jpg':
            case 'jpeg':
                $img = ImageCreateFromJPEG($picturePath);
                break;
            case 'png':
                $img = ImageCreateFromPNG($picturePath);
                break;
            default:
                return false;
        }

        $thumb = ImageCreateTrueColor($newWidth, $newHeight);

        if (in_array($extension, ['gif', 'png'])) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        if (function_exists("imagecopyresampled")) {
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight, $currentwidth, $currentheight);
        } else {
            ImageCopyResized($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight, $currentwidth, $currentheight);
        }
        ob_start();
        switch ($extension) {
            case 'gif':
                $status = imagegif($thumb);
                break;
            case 'jpg':
            case 'jpeg':
                $status = imagejpeg($thumb, null, 100);
                break;
            case 'png':
                $status = imagepng($thumb, null, 0);
                break;
        }
        $imageContent = ob_get_clean();
        $status = $status && acym_writeFile($newFile, $imageContent);
        imagedestroy($thumb);
        imagedestroy($img);

        //We could not create the picture... so let's resize it anyway
        if (!$status) {
            $newFile = $picturePath;
        }

        return ['file' => $newFile, 'width' => $newWidth, 'height' => $newHeight];
    }
}
