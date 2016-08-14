<?php
/**
 * Created by PhpStorm.
 * User: Степан
 * Date: 13.08.2016
 * Time: 0:47
 */

class App
{
    private $uploadsDir = 'uploads/';
    private $availableMethods = ['file', 'files', 'auth', 'login', 'logout', 'registration'];
    private $user_login = null;
    private $user_id = null;
    private $method;
    private $db;

    public function __construct($method)
    {
        header('Content-Type: application/json');
        
        if (!in_array($method, $this->availableMethods)) {
            $this->error(405);
        }

        $this->method = $method;
        $this->db = new mysqli("localhost", "fshare", "RwxuRLhyNspZDJQe", "files");

        if ($this->db->connect_errno) {
            $this->error(500, $this->db->connect_errno . ':' . $this->db->connect_error);
        }

        if (isset($_COOKIE['id'], $_COOKIE['hash'], $_COOKIE['login'])) {
            $user = $this->query($this->selectUserSQL(['*'], "user_id = " . intval($_COOKIE['id']) . " LIMIT 1"))
                ->fetch_assoc();

            if (($user['user_hash'] == $_COOKIE['hash']) && ($user['user_id'] == $_COOKIE['id']) && ($user['user_login'] == $_COOKIE['login'])) {
                $this->user_id = intval($user['user_id']);
                $this->user_login = $user['user_login'];
            } else {
                $this->logout();
            }
        }
    }
    public function execute()
    {
        return json_encode(
            call_user_func_array(
                [$this, $this->method],
                [(object)$_POST]
            )
        );
    }

    private function error($code, $info = '')
    {
        $stats = [
            500 => '500 Internal Server Error',
            400 => '400 Bad Request',
            405 => '405 Method Not Allowed',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found'
        ];
        echo json_encode(['status' => $stats[$code], 'info' => $info, 'code' => $code]);
        exit;
    }
    private function json($array)
    {
        return json_encode(['data' => $array, 'status' => '200 OK', 'code' => 200], JSON_UNESCAPED_SLASHES);
    }
    private function query($SQL)
    {
        return $this->db->query($SQL);
    }
    private function manage($action, $file)
    {
        if ($file->is_delete) {
            $this->error(404, 'file deleted');
        }

        if($file->user_id != $this->user_id) {
            $this->error(403, 'you not owner');
        }

        switch ($action) {
            case 'public': {
                $this->query($this->updateFileSQL($file->id, ['is_private = 0', 'is_public = 1']));
                break;
            }
            case 'private': {
                $this->query($this->updateFileSQL($file->id, ['is_private = 1', 'is_public = 0']));
                break;
            }
            case 'registered': {
                $this->query($this->updateFileSQL($file->id, ['is_private = 0', 'is_public = 0']));
                break;
            }
            case 'delete': {
                $path = $this->uploadsDir . $file->link . '_' . $file->name;
                if (file_exists($path)) {
                    unlink($path);
                }
                $this->query($this->updateFileSQL($file->id, ['is_delete = 1']));
                break;
            }
        }
        return $this->file($file);
    }

    private function selectUserSQL($fields = [], $where)
    {
        $fields = implode(', ', $fields);
        return "SELECT " . $fields . " FROM users WHERE " . $where;
    }
    private function selectFileSQL($fields = [], $where)
    {
        $fields = implode(', ', $fields);
        return "SELECT " . $fields . ", DATE_FORMAT(load_at, '%e/%m/%Y') AS 'date' FROM files WHERE " . $where;
    }
    private function updateFileSQL($id, $toSET)
    {
        $toSET = implode(', ', $toSET);
        return 'UPDATE files SET '.$toSET. ' WHERE id ='. $id;
    }

    function auth()
    {
        if($this->user_id) {
            return $this->json(['auth' => true, 'user_login' => $this->user_login, 'user_id' => $this->user_id]);
        } else {
            return $this->json(['auth' => false]);
        }
    }
    function file($post)
    {
        if (!isset($post->id) || !is_integer(intval($post->id))) {
            $this->error(400);
        }

        if ($this->user_id !== null) {
            $sql = $this->selectFileSQL(
                ['*'],
                '( is_public OR NOT ( is_public OR is_private) OR user_id = ' . $this->user_id . ') AND id = ' . intval($post->id) . ' LIMIT 1'
            );
        } else {
            $sql = $this->selectFileSQL(
                ['*'],
                'is_public AND id = ' . intval($post->id) . ' LIMIT 1'
            );
        }

        $file = $this->query($sql)->fetch_assoc();

        if (isset($post->action)) {
            if (!in_array($post->action, ['delete', 'public', 'private', 'registered'])) {
                $this->error(400, 'File manage action not allowed');
            } else {
                return $this->manage($post->action, (object)$file);
            }
        }

        return $this->json($file);
    }
    function files()
    {
        if($this->user_id !== null) {
            $sql = $this->selectFileSQL(
                ['id', 'name', 'size', 'link', 'user_id'],
                'NOT is_delete AND ( is_public OR NOT ( is_public OR is_private) OR user_id = '.$this->user_id.')'
            );
        } else {
            $sql = $this->selectFileSQL(
                ['id', 'name', 'size', 'link', 'user_id'],
                'NOT is_delete AND is_public '
            );
        }
        $result = $this->query($sql);
        while ($row = $result->fetch_assoc())
            $rows[] = str_replace('"', "''", (array)$row);
        return $this->json($rows);
    }
    function registration($post)
    {
        if (!isset($post->login, $post->password, $post->email)) {
            $this->error(400);
        }

        if (!preg_match("/^[a-zA-Z0-9]+$/", $post->login)) {
            $this->error(400, 'Only latin characters and numbers');
        }

        if (strlen($post->login) < 3 or strlen($post->login) > 20) {
            $this->error(400, 'At least 3 but no more than 254');
        }

        if ($this->query("SELECT user_id FROM users WHERE user_login='" . $post->login . "'")->fetch_row() > 0) {
            $this->error(403, 'User already exist with login ' . $post->login);
        }

        if ($this->query("INSERT INTO users SET user_login='" . $post->login . "', user_password='" . md5(md5($post->password)) . "'")) {
            return $this->json(['user' => $post->login, 'info' => 'registered']);
        }
        $this->error(500);
        return null;
    }
    function login($post)
    {
        if (!isset($post->login, $post->password)) {
            $this->error(400);
        }
        $sql = $this->selectUserSQL(['user_id', 'user_password', 'user_login'], "user_login = '" . $post->login . "' LIMIT 1");
        $data = (object) $this->query($sql)->fetch_assoc();

        if($data->user_password === md5(md5($post->password))) {
            $this->user_id = $data->user_id;
            $hash = md5(time() . 'salt');
            $this->query("UPDATE users SET user_hash='" . $hash . "' WHERE user_id=" . $this->user_id);
            setcookie("login", $post->login, time() + 60 * 60 * 24 * 30);
            setcookie("id", $this->user_id, time() + 60 * 60 * 24 * 30);
            setcookie("hash", $hash, time() + 60 * 60 * 24 * 30);
            return $this->json(['info' => 'login']);
        } else {
            $this->error(401, 'password not mach');
        }
        return null;
    }
    function logout()
    {
        setcookie("id", "", time() - 3600*24*30*12, "/");
        setcookie("hash", "", time() - 3600*24*30*12, "/");
        setcookie("login", "", time() - 3600*24*30*12, "/");
        return $this->json(['info' => 'logout']);
    }
};


if (isset($_GET['ajax'], $_GET['method'])) {
    $app = new App($_GET['method']);
    echo $app->execute();
} else {
    ob_start();
    include("body.html");
    echo ob_get_clean();
}
