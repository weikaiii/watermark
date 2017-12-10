<?php

    $edgePadding=15;                        // used when placing the watermark near an edge
    $quality=100;                           // used when generating the final image
    $default_watermark='img/watermark.png'; // your watermark here
	$DirPath = 'results';					// watermarked images destination
	$erroroutput = "";						//custom error for uploaded not jpg
	
    //Beginning of image posting
    if(isset($_POST['process'])){
		$original=$_FILES['filejpg']['tmp_name'];
        // an image has been posted, let's get to the nitty-gritty
        if(isset($_FILES['filejpg']) && $_FILES['filejpg']['error']==0){
			
			// $finfo = finfo_open(FILEINFO_MIME); 
			// $mimetype = finfo_file($finfo,$original); 
			// finfo_close($finfo);
			// $mimetype = substr($mimetype, 0, strpos($mimetype, ';'));
			
			$mimetype = mime_content_type($original);
			
            // be sure that the other options we need have some kind of value
            $_POST['save_as']='jpeg';
            $_POST['verticalpos']='center';
            $_POST['horizontalpos']='center';
            $_POST['wotermarksayz']='1';
			
            if($mimetype != 'image/jpeg'){
				$erroroutput = "
					<div class='alert alert-danger'>
						<center>
							<strong><h4>Error!</strong> Only '<u>original jpg</u>' images is allowed to be watermarked.</h4>
						</center>
					</div>";
				goto skip;
			}
			
			if (!file_exists('results')) {
				mkdir('results', 0777, true); // Creates folder results if not exists
			}
			
            // file upload success
            $size=getimagesize($original);
            if($size[2]==2){
                // it was a JPEG image, so we're OK so far
                
				date_default_timezone_set("Asia/Manila");//set default timezone to Manila, Philippines
				//generate new filename
                $target_name=date('YmdHis').'_'.preg_replace('`[^a-z0-9-_.]`i','',$_FILES['filejpg']['name']);
                $target=dirname(__FILE__).'/results/'.$target_name;
                $watermark=$default_watermark;
                $wmTarget=$watermark.'.tmp';

                $origInfo = getimagesize($original); 
                $origWidth = $origInfo[0]; 
                $origHeight = $origInfo[1]; 

                $waterMarkInfo = getimagesize($watermark);
                $waterMarkWidth = $waterMarkInfo[0];
                $waterMarkHeight = $waterMarkInfo[1];
        
                // watermark sizing info
                if($_POST['wotermarksayz']=='larger'){
                    $placementX=0;
                    $placementY=0;
                    $_POST['horizontalpos']='center';
                    $_POST['verticalpos']='center';
                	$waterMarkDestWidth=$waterMarkWidth;
                	$waterMarkDestHeight=$waterMarkHeight;
                    
                    // both of the watermark dimensions need to be 5% more than the original image...
                    // adjust width first.
                    if($waterMarkWidth > $origWidth*1.05 && $waterMarkHeight > $origHeight*1.05){
                    	// both are already larger than the original by at least 5%...
                    	// we need to make the watermark *smaller* for this one.
                    	
                    	// where is the largest difference?
                    	$wdiff=$waterMarkDestWidth - $origWidth;
                    	$hdiff=$waterMarkDestHeight - $origHeight;
                    	if($wdiff > $hdiff){
                    		// the width has the largest difference - get percentage
                    		$sizer=($wdiff/$waterMarkDestWidth)-0.05;
                    	}else{
                    		$sizer=($hdiff/$waterMarkDestHeight)-0.05;
                    	}
                    	$waterMarkDestWidth-=$waterMarkDestWidth * $sizer;
                    	$waterMarkDestHeight-=$waterMarkDestHeight * $sizer;
                    }else{
                    	// the watermark will need to be enlarged for this one
                    	
                    	// where is the largest difference?
                    	$wdiff=$origWidth - $waterMarkDestWidth;
                    	$hdiff=$origHeight - $waterMarkDestHeight;
                    	if($wdiff > $hdiff){
                    		// the width has the largest difference - get percentage
                    		$sizer=($wdiff/$waterMarkDestWidth)+0.05;
                    	}else{
                    		$sizer=($hdiff/$waterMarkDestHeight)+0.05;
                    	}
                    	$waterMarkDestWidth+=$waterMarkDestWidth * $sizer;
                    	$waterMarkDestHeight+=$waterMarkDestHeight * $sizer;
                    }
                }else{
	                $waterMarkDestWidth=round($origWidth * floatval($_POST['wotermarksayz']));
	                $waterMarkDestHeight=round($origHeight * floatval($_POST['wotermarksayz']));
	                if($_POST['wotermarksayz']==1){
	                    $waterMarkDestWidth-=2*$edgePadding;
	                    $waterMarkDestHeight-=2*$edgePadding;
	                }
                }

                // OK, we have what size we want the watermark to be, time to scale the watermark image
                resize_png_image($watermark,$waterMarkDestWidth,$waterMarkDestHeight,$wmTarget);
                
                // get the size info for this watermark.
                $wmInfo=getimagesize($wmTarget);
                $waterMarkDestWidth=$wmInfo[0];
                $waterMarkDestHeight=$wmInfo[1];

                $differenceX = $origWidth - $waterMarkDestWidth;
                $differenceY = $origHeight - $waterMarkDestHeight;

				$placementX =  round($differenceX / 2);
				$placementY =  round($differenceY / 2);
				
                $resultImage = imagecreatefromjpeg($original);
				
                imagealphablending($resultImage, TRUE);
        
                $finalWaterMarkImage = imagecreatefrompng($wmTarget);
                $finalWaterMarkWidth = imagesx($finalWaterMarkImage);
                $finalWaterMarkHeight = imagesy($finalWaterMarkImage);
        
                imagecopy($resultImage,
                          $finalWaterMarkImage,
                          $placementX,
                          $placementY,
                          0,
                          0,
                          $finalWaterMarkWidth,
                          $finalWaterMarkHeight
                );
                
                imagejpeg($resultImage,$target,$quality); 

                imagedestroy($resultImage);
                imagedestroy($finalWaterMarkImage);

                unlink($wmTarget);
            }
			skip:
        }
    }
//watermark resizing function
function resize_png_image($img,$newWidth,$newHeight,$target){
    $srcImage=imagecreatefrompng($img);
    if($srcImage==''){
        return FALSE;
    }
    $srcWidth=imagesx($srcImage);
    $srcHeight=imagesy($srcImage);
    $percentage=(double)$newWidth/$srcWidth;
    $destHeight=round($srcHeight*$percentage)+1;
    $destWidth=round($srcWidth*$percentage)+1;
    if($destHeight > $newHeight){
        // if the width produces a height bigger than we want, calculate based on height
        $percentage=(double)$newHeight/$srcHeight;
        $destHeight=round($srcHeight*$percentage)+1;
        $destWidth=round($srcWidth*$percentage)+1;
    }
    $destImage=imagecreatetruecolor($destWidth-1,$destHeight-1);
    if(!imagealphablending($destImage,FALSE)){
        return FALSE;
    }
    if(!imagesavealpha($destImage,TRUE)){
        return FALSE;
    }
    if(!imagecopyresampled($destImage,$srcImage,0,0,0,0,$destWidth,$destHeight,$srcWidth,$srcHeight)){
        return FALSE;
    }
    if(!imagepng($destImage,$target)){
        return FALSE;
    }
    imagedestroy($destImage);
    imagedestroy($srcImage);
    return TRUE;
}
?>
