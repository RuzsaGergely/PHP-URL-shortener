<?php
require "database.php";

$params = explode('/', $_SERVER['REQUEST_URI']);

function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(!empty($params[1])){
        $stmt = $conn->prepare("SELECT * FROM `records` WHERE `url`=?");
        $stmt->bind_param("s", $params[1]);
        $stmt->execute();
        $result = $stmt->get_result();
        $check = Array();
        while ($row = $result->fetch_assoc()) {
            $check["type"] = $row["type"];
            $check["text"] = $row["text"];
        }
    }

} else if($_SERVER["REQUEST_METHOD"] == "POST"){
    $url_name = "";
    $success = null;
    if(!isset($_POST["generateName"]) && !empty($_POST["custom_url"])){
        $url_name = $_POST["custom_url"];
    } else {
        $url_name = generateRandomString();
    }

    if(!isset($_POST["data"]) || empty($_POST["data"])){
        $success = false;
    }

    $edit_token = generateRandomString(10);

    if($success === null){
        $stmt = $conn->prepare("INSERT INTO `records`(`url`, `type`, `text`, `edit_token`) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $url_name, $_POST["data_type"], $_POST["data"], $edit_token);
    }
    if($success === null){
        if(!$stmt->execute()){
            $success = false;
        } else {
            $success = true;
            unset($_POST);
        }
    } else {
        $success = false;

    }


}

?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>RuzGer URL rövidítő és szövegtároló</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <style>
        a {
            color: white;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
<div class="d-flex justify-content-center mt-3">
    <div>
        <?php
        if($check["type"] == "link") {
            header("Location: " . $check["text"]);
            exit();
        }
        if($check["type"] == "text"){
            echo $check["text"];
        } else {
            $form = <<<HTML
        <form method="post">
            <div class="mb-3">
                <label for="custom_url" class="form-label">Egyéni név</label>
                <input type="text" class="form-control" name="custom_url" id="custom_url" value="{$_POST["custom_url"]}" disabled>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="generateName" id="generateName" checked>
                <label class="form-check-label" for="generateName">Generáljon nevet automatikusan!</label>
            </div>
            <div class="mb-3">
                <label for="data_type" class="form-label">Adat típusa:</label>
                <select class="form-select" aria-label="Adattípus választó" aria-describedby="datatypeHelp" name="data_type" id="data_type">
                    <option value="link" selected>Link</option>
                    <option value="text">Szöveg</option>
                </select>
                <div id="datatypeHelp" class="form-text">A link átirányít, a szöveg csak megjelenít!</div>
            </div>
            <div class="mb-3">
                <label for="data" class="form-label">A linkhez tartozó adat:</label>
                <input type="text" class="form-control" name="data" id="data" value="{$_POST["data"]}">
            </div>
            <button type="submit" class="btn btn-primary">Mentés</button>
        </form>
HTML;
echo $form;
        }

        $success_output = <<<HTML
    <br>
    <div>
    <div class="card text-white bg-success">
  <div class="card-body">
    <p>Sikeresen létrehoztad a linket!</p>
    <p>A linked: <a href="https://url.ruzger.hu/{$url_name}">https://url.ruzger.hu/{$url_name}</a></p>
    <p>A szerkesztő kulcsod: {$edit_token}</p>
    <p>A szerkesztő gyorslinked: <a href="https://url.ruzger.hu/edit.php?key={$edit_token}&url={$url_name}">https://url.ruzger.hu/edit.php?key={$edit_token}&url={$url_name}</a></p>
  </div>
</div>
<br>
<div id="qrcode" class="d-flex justify-content-center mt-3"></div>
</div>
HTML;

        $failed_output = <<<HTML
     <br>
    <div>
    <div class="card text-white bg-danger">
  <div class="card-body">
    <p>Nem sikerült a linket létrehozni! A megadott vagy generált URL már létezik. Kérlek próbáld újra!</p>
  </div>
</div>
</div>
HTML;
        if($_SERVER["REQUEST_METHOD"] == "POST"){
            if($success){
                echo $success_output;
                echo "<script type=\"text/javascript\">new QRCode(document.getElementById(\"qrcode\"), \"https://url.ruzger.hu/" . $url_name. "\");</script>";
            } else {
                echo $failed_output;
            }
        }

        ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
<script>
    const checkbox = document.getElementById('generateName')

    checkbox.addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            document.getElementById("custom_url").disabled = true;
        } else {
            document.getElementById("custom_url").disabled = false;
        }
    })

</script>
</body>
</html>
