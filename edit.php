<?php
session_start();
require "database.php";

$reportable = false;

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(isset($_GET["url"], $_GET["key"]) && !empty($_GET["url"] . $_GET["key"])){
        $reportable = true;
        $stmt = $conn->prepare("SELECT * FROM `records` WHERE `url`=?");
        $stmt->bind_param("s", $_GET["url"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = Array();
        while ($row = $result->fetch_assoc()) {
            if($row["edit_token"] != $_GET["key"]){
                header("location: edit.php");
                exit();
            } else {
                $data["data"] = $row["text"];
                $data["type"] = $row["type"];
            }
        }
        $_SESSION["url"] = $_GET["url"];
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $stmt = $conn->prepare("UPDATE `records` SET `type`=?,`text`=? WHERE `url`=?");
    $stmt->bind_param("sss", $_POST["type"], $_POST["data"], $_SESSION["url"]);
    $stmt->execute();
    session_destroy();
}

?>

<!doctype html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>RuzGer URL rövidítő és szövegtároló - szerkesztő</title>
</head>
<body>
<p>Szerkesztő</p>
<?php
$output = "";
if ($reportable){
    if($data["type"] == "link"){
        $link_type = "selected";
    } else {
        $text_type = "selected";
    }
    $output = <<<HTML
    <form method="post">
    <input type="text" name="data" id="data" placeholder="data" value="{$data["data"]}">
    <br>
    <br>
    <select name="type" id="type">
        <option value="link" {$link_type}>Link</option>
        <option value="text" {$text_type}>Szöveg</option>
    </select>
    <br>
    <br>
    <input type="submit" value="Mentés">
    </form>
HTML;
} else {
$output = <<<HTML
<form method="get">
<input type="text" name="url" id="url" placeholder="URL">
<br>
<br>
<input type="text" name="key" id="key" placeholder="Szerkesztő kulcs">
<br>
<br>
<input type="submit" value="Szerkesztés">
</form>
HTML;

}
echo $output;
?>
</body>
</html>
