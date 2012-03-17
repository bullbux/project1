<?php
class Request{
	public  $request = array();
	public function __construct(){
		$this->request = array_merge($_FILES, $_GET, $_POST);
	}

        function has($string){
            if(is_string($string) && (isset($_GET[$string]) || isset($_POST[$string]))){
                return true;
            }

            return false;
        }
}
?>