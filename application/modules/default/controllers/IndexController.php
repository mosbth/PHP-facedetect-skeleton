<?php

class Default_IndexController extends Zend_Controller_Action
{
    /**
     * @var TuneHog_Discovery_Api_Mobile
     */
    protected $signer;

    protected $faceCascades = array(
        'haarcascade_frontalface_default.xml',
        'haarcascade_frontalface_alt2.xml',
        'haarcascade_frontalface_alt_tree.xml',
        'haarcascade_profileface.xml',
        'haarcascade_frontalface_alt.xml',
    );

    protected $faceElementsCascades = array(
        'haarcascade_eye.xml',
        'haarcascade_mcs_mouth.xml',
        'haarcascade_eye_tree_eyeglasses.xml',
        'haarcascade_mcs_eyepair_small.xml',
        'haarcascade_mcs_eyepair_big.xml',
        'haarcascade_mcs_righteye.xml',
        'haarcascade_mcs_lefteye.xml',
        'haarcascade_mcs_nose.xml',
    );


    public function init()
    {
        $this->signer = new HTTP_UrlSigner("very-secret-word",'/default/index/crop/*');
    }

    public function generateAction() {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        $xml = simplexml_load_file('http://api.flickr.com/services/feeds/photos_public.gne?tags=face&format=rss_100');

        $files = array();
        $path = realpath(APPLICATION_PATH.'/../public/images/random/');
        // Parse the feed
        foreach($xml->item as $item) {
            preg_match_all('/(alt|title|src)="([^"]*)"/i',$item->description, $result);

            $fileUrl = str_replace('_m.jpg', '_b.jpg', $result[2][1]);
            print $fileUrl.'<br>';
            flush();
            $filename = uniqid().'.jpg';
            $fileData = file_get_contents($fileUrl);
            file_put_contents($path.'/'.$filename,$fileData);
            $files[] = $filename;
            if (count($files) >= 20) break;
        }
    }

    public function indexAction()
    {
        // Get Contents from flickr


        $images = array();
        $file = APPLICATION_PATH.'/../public/images/random/*.jpg';
        $files = glob($file);

        $resizer = new HTTP_ImageResizer($this->signer, null);

        foreach($files as $file) {
            $file = basename($file);

            $paramsToPassToProcessor = array(
                "height" => 500,
                "width" => 127,
                'fit' => 0,
                'crop' => 1,
                'format' => 'jpeg',
                'quality' => 100,
                'image' => $file
            );
            $images[] =  $resizer->getUrl($paramsToPassToProcessor);;
        }

        $this->view->images = $images;//
    }

    public function cropAction() {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        $resizer = new HTTP_ImageResizer($this->signer, function ($params) {
            $file = 'images/random/' . $params['image'];
            return file_get_contents($file);
        });

        $resizer->main($_SERVER['REQUEST_URI']);
    }

    public function resizeAction()
    {
	    set_time_limit(300);
        $file = APPLICATION_PATH.'/../public/images/random/'.$_GET['file'];
        $file = realpath($file);
        $img = imagecreatefromjpeg($file);


        $maxW = !empty($_GET['width']) ? $_GET['width'] : 0;
        $maxH = !empty($_GET['height']) ? $_GET['height'] : 0;

        list($w, $h) = getimagesize($file);
        $ratio = max($maxW / $w, $maxH / $h);

        $newIm = imagecreatetruecolor(round($w*$ratio), round($h*$ratio));
        imagecopyresampled($newIm, $img, 0, 0, 0, 0, round($w*$ratio), round($h*$ratio), $w, $h);

        $tmp = tempnam(sys_get_temp_dir(), 'ir');
        imagepng($newIm, $tmp);

        $img = $newIm;
        /*
        * haarcascade_frontalface_alt2.xml
        * haarcascade_frontalface_default.xml
        * haarcascade_frontalface_alt_tree.xml
        * haarcascade_profileface.xml
        * haarcascade_frontalface_alt.xml
        *
        * haarcascade_eye.xml
        * haarcascade_mcs_mouth.xml
        * haarcascade_eye_tree_eyeglasses.xml
        * haarcascade_mcs_eyepair_small.xml
        * haarcascade_mcs_eyepair_big.xml
        * haarcascade_mcs_righteye.xml
        * haarcascade_mcs_lefteye.xml
        * haarcascade_mcs_nose.xml

        * haarcascade_mcs_rightear.xml
        * haarcascade_mcs_leftear.xml

        * haarcascade_lefteye_2splits.xml
        * haarcascade_righteye_2splits.xml

        * haarcascade_fullbody.xml
        * haarcascade_lowerbody.xml
        * haarcascade_mcs_upperbody.xml
        * haarcascade_upperbody.xml
        */
        $faces = face_detect($tmp, "/usr/local/share/OpenCV/haarcascades/haarcascade_frontalface_default.xml", "/usr/local/share/OpenCV/haarcascades/haarcascade_eye.xml", true );

        if (!$faces) {
            $faces = array();
        }
        $color = imagecolorallocate($newIm, 0, 255, 0);

        foreach($faces AS $face) {
            $color = imagecolorallocate($newIm, 0, 255, 0);
            imagepolygon($newIm,
                array(
                    $face['x'], $face['y'],
                    $face['x'] + $face['w'], $face['y'],
                    $face['x'] + $face['w'], $face['y'] + $face['h'],
                    $face['x'], $face['y'] + $face['h'],
                ),
                4,
                $color);
            $color = imagecolorallocate($newIm, 255, 255, 0);
            foreach($face['eyes'] AS $eye) {

                imagepolygon($newIm,
                    array(
                        $face['x'] + $eye['x'], $face['y'] + $eye['y'],
                        $face['x'] + $eye['x'] + $eye['w'], $face['y'] + $eye['y'],
                        $face['x'] + $eye['x'] + $eye['w'], $face['y'] + $eye['y'] + $eye['h'],
                        $face['x'] + $eye['x'], $face['y'] + $eye['y'] + $eye['h'],
                    ),
                    4,
                    $color);
            }
        }

        header("Content-Type: image/png");
        imagepng($newIm);
        unlink($tmp);
        die;
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        $resizer = new HTTP_ImageResizer($this->signer, function ($params) {
            $file = 'images/' . $params['image'];
            var_dump(face_count($file, 'cascade.xml'));
            var_dump(face_detect($file, 'cascade.xml'));

            return file_get_contents($file);
        });

        $resizer->main($_SERVER['REQUEST_URI']);
        die;
    }

    protected  function _resize($file, $maxW, $maxH) {
        list($w, $h) = getimagesize($file);
        $ratio = max($maxW / $w, $maxH / $h);
        $img = imagecreatefromjpeg($file);
        $newIm = imagecreatetruecolor(round($w*$ratio), round($h*$ratio));
        imagecopyresampled($newIm, $img, 0, 0, 0, 0, round($w*$ratio), round($h*$ratio), $w, $h);

        $tmp = tempnam(sys_get_temp_dir(), 'ir');
        imagepng($newIm, $tmp);
        imagedestroy($img);
        imagedestroy($newIm);
        return $tmp;
    }

    public function testAction() {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $cascadePath = '/usr/local/share/OpenCV/haarcascades/';

        $file = APPLICATION_PATH.'/../public/images/random/*.jpg';
        $files = glob($file);

        foreach($files as $file) {
            $rows = array(
                0 => array( '', '' )//row 0 - header
            );

            for($i = 500; $i <= 1200; $i += 100) {
                $newFile = $this->_resize($file, $i, 0);
                $rows[0][] = $i; //add width to header

                $row = 1;
                foreach($this->faceCascades AS $faceCascade) {
                    foreach($this->faceElementsCascades AS $faceElement) {
                        if (!isset($rows[$row])) {
                            $rows[$row] = array($faceCascade, $faceElement); // add first elements
                        }

                        $rows[$row][] = face_detect($newFile, $cascadePath.$faceCascade, $cascadePath.$faceElement);
                        $row ++;
                    }
                }
                unlink($newFile);
            }

            flush();
            $pathInfo = pathinfo($file);
            $tmp = tempnam(realpath(APPLICATION_PATH.'/../data/reports'), 'statistic_');
            file_put_contents($tmp, serialize(array(
                'file' => $pathInfo['basename'],
                'rows' => $rows
            )));
        }
    }

    public function reportAction() {
        $filePart = realpath(APPLICATION_PATH.'/../data/reports').'/statistic_*';
        $files = glob($filePart);
        $filesArr = array();
        $histogram = array();
        foreach($files as $file) {
            $fileStats = unserialize(file_get_contents($file));
            $filesArr[] = $fileStats;
            list($hader) = array_splice($fileStats['rows'], 0, 1);
            foreach($fileStats['rows'] as $i => $row) {
                foreach($row as $cellIndex => $cell) {
                    $cellname = $hader[$cellIndex];
                    @$histogram[$cellname] += is_array($cell) ? count($cell) : $cell;
                }
            }
        }
        $this->view->files = $filesArr;
        $this->view->histogram = $histogram;
    }
}

