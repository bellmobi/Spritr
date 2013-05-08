<?php
/********************************************* 
 * This code has been modified by Bunchou Plong, Bellmobi.com
 * Fixed background transparancy
 * Export SCSS file with @mixin and @2x format
 * 
 * Get the idea from:
 * http://webcodingeasy.com/PHP-classes/CSS-sprite-class-for-creating-sprite-image-and-CSS-code-generation
**********************************************/

class spritr
{
	
	private $images = array();
	private $errors = array();
	private $sprite_img_name 	= 'sprites';
	private $sprite_img_type 	= 'png';
	private $sprite_scss_name 	= '_sprites';
	
	//gets errors
	public function get_errors(){
		return $this->errors;
	}
	
	
	// Open a known directory, and proceed to read its contents
	public function init($folder = './images', $file_allowed = array('JPG', 'JPEG', 'GIF', 'PNG')){

		$dir = $folder . '/' . 'sprites';
		if (is_dir($dir)) {
		    if ($dh = opendir($dir)) {
		        while (($file = readdir($dh)) !== false) {
		        	$_path_to_file = $dir . '/' . $file;
		        	if (in_array(strtoupper(pathinfo($file, PATHINFO_EXTENSION)), $file_allowed)){
		        		$this->add_image($_path_to_file);
					}
		        }
		        closedir($dh);
		    }
		}
		
		if (!empty($this->errors)) print_r($this->errors);
		else $this->output_image($folder);
		
	}
	
	/*
	 * adds new image
	 */
	private function add_image($image_path){
		if(file_exists($image_path))
		{
			$info = getimagesize($image_path);
			if(is_array($info))
			{
				$new = sizeof($this->images);
				$this->images[$new]["path"] = $image_path;
				$this->images[$new]["width"] = $info[0];
				$this->images[$new]["height"] = $info[1];
				$this->images[$new]["mime"] = $info["mime"];
				$type = explode("/", $info['mime']);
				$this->images[$new]["type"] = $type[1];
				$this->images[$new]["@2x"] = 0;
				$imagename  = pathinfo($image_path, PATHINFO_FILENAME);
				if (substr($imagename, -3) == '@2x'){
					$imagename = substr_replace($imagename, '', -3);
					$this->images[$new]["@2x"] = 1;
				}
				$this->images[$new]["filename"] = $imagename;
			
			}
			else
			{
				$this->errors[] = "Provided file \"".$image_path."\" isn't correct image format";
			}
		}
		else
		{
			$this->errors[] = "Provided file \"".$image_path."\" doesn't exist";
		}
	}
	
	
	//calculates width and height needed for sprite image
	private function total_size(){
		$arr = array("width" => 0, "height" => 0);
		foreach($this->images as $image)
		{
			if($arr["width"] < $image["width"])
			{
				$arr["width"] = $image["width"];
			}
			$arr["height"] += $image["height"];
		}
		return $arr;
	}
	
	//creates sprite image resource
	private function create_image(){
		$total = $this->total_size();
		$sprite = imagecreatetruecolor($total["width"], $total["height"]);
		imagesavealpha($sprite, true);
		$transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
		imagefill($sprite, 0, 0, $transparent);
		$top = 0;
		foreach($this->images as $image)
		{
			$func = "imagecreatefrom".$image['type'];
			$img = $func($image["path"]);
			//imagecopy( $sprite, $img, ($total["width"] - $image["width"]), $top, 0, 0,  $image["width"], $image["height"]);
			imagecopy( $sprite, $img, 0, $top, 0, 0,  $image["width"], $image["height"]);
			$top += $image["height"];
		}
		return $sprite;
	}
	
	//outputs image to browser (makes php file behave like image)
	public function output_image($filename = '.'){
		$sprite = $this->create_image();
		$func = "image".$this->sprite_img_type;
		$func($sprite, $filename.'/'.$this->sprite_img_name.'.'.$this->sprite_img_type);
		ImageDestroy($sprite);
	}
	
	/*
	 * generates scss code
	 */
	public function generate_scss($scss_path = '.', $url_path = '/images'){
		if (empty($this->errors)) {
			
			$sprite 		= $this->sprite_img_name.'.'.$this->sprite_img_type;
			$sprite_path 	= $url_path.'/'.$sprite;
			$total 			= $this->total_size();
			
			$fp = fopen($scss_path.'/'.$this->sprite_scss_name.'.scss', 'w');
			$content  = '$sprites-url: url("'.$sprite_path.'");'."\n";
			$content .= "\n";
			$content .= '$sprites-width: '.$total["width"].'px;'."\n";
			$content .= '$sprites-height: '.$total["height"].'px;'."\n";
			$content .= "\n";
			
			$content .= $this->css_block('VARIABLES', $sprite_path, $total["width"], $total["height"]);
			$content .= $this->css_block('MIXIN', $sprite_path, $total["width"], $total["height"]);
			$content .= $this->css_block('CLASS', $sprite_path, $total["width"], $total["height"]);
					
			fwrite($fp, $content);
			fclose($fp);
			
			echo "Processing complete.";
		}
	}

	// generate CSS block
	private function css_block($option = 'VARIABLES', $sprite_path, $total_width, $total_height){
		$content = '';
		
		$top = 0;
		$counter = count($this->images);
		
		foreach($this->images as $image){
			$counter--;
			switch ($option) {
				case 'VARIABLES':
					$content .= '$sprites-'.$image['filename'].'-x: 0px;'."\n";
					$content .= '$sprites-'.$image['filename'].'-y: '.$top.'px;'."\n";
					$content .= '$sprites-'.$image['filename'].'-width: '.$image['width'].'px;'."\n";
					$content .= '$sprites-'.$image['filename'].'-height: '.$image['height'].'px;'."\n";
					$content .= "\n";
					break;
				case 'MIXIN':
					$content .= '@mixin sprites-'.$image['filename'].'() {'."\n";
					$content .= '  background: url("'.$sprite_path.'") no-repeat 0px '.$top.'px;'."\n";
					if ($image['@2x']) {
					$content .= '  @include background-size('.($total_width/2).'px '.($total_height/2).'px);'."\n";
					}
					$content .= '  width: '.$image['width'].'px;'."\n";
					$content .= '  height: '.$image['height'].'px;'."\n";
					$content .= '}'."\n";
					$content .= "\n";
					break;
				case 'CLASS':
					$content .= '.sprites-'.$image['filename'].' {'."\n";
					$content .= '  background: url("'.$sprite_path.'") no-repeat 0px '.$top.'px;'."\n";
					if ($image['@2x']) {
					$content .= '  @include background-size('.($total_width/2).'px '.($total_height/2).'px);'."\n";
					}
					$content .= '  width: '.$image['width'].'px;'."\n";
					$content .= '  height: '.$image['height'].'px;'."\n";
					$content .= '}'."\n";
					$content .= ($counter > 0)?"\n":'';
					break;
				default:
					break;
			}
			$top -= $image["height"];
		}
		
		return $content;
	}
	
	
	
}
?>