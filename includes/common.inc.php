<?php 
	include_once(dirname(dirname(__FILE__)).'/constants.php');
	include_once(dirname(__FILE__).'/define.php');
        include_once(dirname(__FILE__).'/configure.php');
	class include_resource{
		private $css_file	= '';
		private $js_file	= '';
		private $image_file = '';
                private $resources = '';
                private $include_once_js = array();
                private $include_once_css = array();
		public function __construct(){	
			$this->css_file = WWW_ROOT.'/resources/css/';
			$this->js_file = WWW_ROOT.'/resources/js/';
			$this->image_file = WWW_ROOT.'/resources/images/';
                        $this->resources = WWW_ROOT.'/resources/';
		}
		public function css($filepath){
                        if(!in_array($filepath, $this->include_once_css)){
                            $this->include_once_css[] = $filepath;
                            return "<link href='".$this->css_file.$filepath."' rel='stylesheet' type='text/css' />";
                        }
		}
		public function js($filepath){
                        if(!in_array($filepath, $this->include_once_js)){
                            $this->include_once_js[] = $filepath;
                            if(preg_match('/http:\/\//i', $filepath)){
                                $jsPath = '';
                            }else{
                                $jsPath = $this->js_file;
                            }
                            return "<script type='text/javascript' src='".$jsPath.$filepath."' ></script>";
                        }
		}
                public function resources($filepath = ''){
			return $this->resources . $filepath;
		}
                public function image($path=null,$params = array()){
                       $stringAttributes = "";
                       if($path != null){
                               $position = strpos($path,"http:");
                               if($position !== 0){
                                   $path = $this->image_file.$path;
                               }
                               if(is_array($params)){
                                   foreach($params as $key => $value){
                                           $stringAttributes .=" ".$key."='".$value."' ";
                                   }
                               }
                       }
                       return "<img src='".$path."' ".$stringAttributes." />";
               }

               public function showImage($path=null, $params = array(), $onlySrc = false){
                   $stringAttributes = "";
                   $dims = '';
                   $height = '';
                   $width = '';
                   if($path != null){
                           $position = strpos($path,"http:");
                           if($position !== 0){
                               $path = $this->image_file.$path;
                           }
                           if(is_array($params)){
                               if(isset($params['dims'])){
                                    $params = array_diff_key($params, $params['dims']);
                                    foreach($params['dims'] as $k => $v){
                                        if($k == 'width')
                                            $width = ' width="'.$v.'"';
                                        elseif($k == 'height'){
                                           $height = ' height="'.$v.'"';
                                        }
                                        $dims .= '&amp;' . $k . '=' . $v;
                                    }
                                    unset($params['dims']);
                               }
                               foreach($params as $key => $value){
                                    $stringAttributes .=" ".$key."='".$value."' ";
                               }
                           }
                   }
                   if($onlySrc)
                        return WWW_ROOT."/library/timthumb/timthumb.php?src=".$path.$dims;
                        
                   return "<img src='".WWW_ROOT."/library/timthumb/timthumb.php?src=".$path.$dims."' ".$stringAttributes." $width $height />";
               }
	}
?>