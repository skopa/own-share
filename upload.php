<?php
header('Content-type: text/html; charset=utf-8');

class File {
	private $dir;

	public $size;
	public $link;
	public $name;

	private function link($length = 7)
	{
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
		return $result;
	}

	public function __construct($dir)
	{
		$this->dir = $dir;
		$this->size = $_FILES['upl']['size'];
		$this->name = $_FILES['upl']['name'];
		$this->link = $this->link();
	}
	public function save()
	{
		return (bool)move_uploaded_file(
			$_FILES['upl']['tmp_name'],
			$this->dir . $this->link . '_' . $this->name
		);
	}
}

class App {
	private $uploadsDir = 'uploads/';
	private $db;
	private $user;

	public function __construct()
	{
		$this->user = (object)['user_login' => 'guest', 'user_id' => 1];
		$this->db = new mysqli("localhost", "fshare", "RwxuRLhyNspZDJQe", "files");
		if ($this->db->connect_errno) {
			$this->error('DB connect error');
		}

		if (isset($_COOKIE['id'], $_COOKIE['hash'], $_COOKIE['login'])) {
			$data = $this->db
				->query("SELECT * FROM users WHERE user_id = " . intval($_COOKIE['id']) . " LIMIT 1")
				->fetch_assoc();
			if (($data['user_hash'] == $_COOKIE['hash']) && ($data['user_id'] == $_COOKIE['id']) && ($data['user_login'] == $_COOKIE['login'])) {
				$this->user = (object)$data;
			}
		}
	}
	private function sizeString($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes?log($bytes):0)/log(1024));
		$pow = min($pow, count($units)-1);
		$bytes /= pow(1024, $pow);
		return round($bytes, 2).' '.$units[$pow];
	}
	private function file()
	{
		if (!isset($_FILES['upl']) || $_FILES['upl']['error'] != 0) {
			return false;
		}

		return new File($this->uploadsDir);
	}
	private function error($info = '')
	{
		echo $this->json(['status' => 'error', 'info' => $info]);
		exit;
	}
	private function json($data) {
		return json_encode($data);
	}

	public function execute() {
		$file = $this->file();

		if (!$file) {
			$this->error('file error');
		}

		$save = $this->db->prepare("INSERT INTO files (name, size, link, user_id,  user) VALUES (?,?,?,?,?)");
		$save->bind_param(
			'sssis',
			$file->name,
			$this->sizeString($file->size),
			$file->link,
			$this->user->user_id,
			$this->user->user_login
		);

		if($file->save()) {
			$save->execute();
			return $this->json(['status' => 'success']);
		}

		$this->error();
		return null;
	}
}

$app = new App();
echo $app->execute();