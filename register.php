<?

// Страница регситрации нового пользователя


# Соединямся с БД

$mysqli = new mysqli("localhost", "fshare", "RwxuRLhyNspZDJQe", "files");
if ($mysqli->connect_errno) {
    echo "MySQL connect error: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

if(isset($_POST['login']) && isset($_POST['password']) && isset($_POST['email']))
{
	$err = array();
    if(!preg_match("/^[a-zA-Z0-9]+$/",$_POST['login']))
        $err[] = "Лише букви або цифри латинського алфавіту!";
    if(strlen($_POST['login']) < 3 or strlen($_POST['login']) > 20)
        $err[] = "Не менше 3 але не більше 20 символів";
    $query = "SELECT user_id FROM users WHERE user_login='".($_POST['login'])."'";
    $res = $mysqli->query($query);
	if($res->fetch_row() > 0)
        $err[] = "Логін занятий";
    if(count($err) == 0)
    {
        $login = $_POST['login'];
		$password = md5(md5(trim($_POST['password'])));
		$query ="INSERT INTO users SET user_login='".$login."', user_password='".$password."'";
        $res = $mysqli->query($query);
		header("Location: login.php");exit();
    }
    else
    {
        print "<b>При реєстрації виникла помилка:</b><br>";
        foreach($err AS $error)
        {
            print $error."<br>";
        }
    }
}

?>

<form id="loginf" method="POST" >
<hd>Реєстрація:</hd><br>
<input required autocomplete="off" placeholder="Логін" name="login" type="text"><br>
<input required autocomplete="off" placeholder="e-mail" name="email" type="email"><br>
<input required autocomplete="off" placeholder="Пароль" name="password" type="password"><br>
<input name="submit" onClick="submt('register.php', '#login', '#loginf')" type="button" value="Зареєструватися!">
<a onClick="step('login.php')">Вхід</a>
</form>