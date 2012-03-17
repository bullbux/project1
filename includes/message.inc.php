<?php 
class message{
	public $head = '';
	public $success = array();
	public $error = array();
	public $notice = array();
	function __construct(){
	}

	function message(){
		$class = "";
		$message="";
		if(count($this->error)>0){
			$class = "error";
			foreach($this->error as $msg){
				$message.=$msg."<br />";
			}
		}
		else if(count($this->success)>0){
			$head="Error in processing";
			$class = "success";
			foreach($this->success as $msg){
				$message.=$msg."<br />";
			}
		}
			$message = "<div class='".$class."'><span>".$this->head."</span><p>".$message."</p></div>";
		return $message;
	}
}