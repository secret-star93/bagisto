<html>

    <?php
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $cssUrl = $actual_link .'/'. 'CSS/style.css';
        $logo =  $actual_link .'/'. 'Images/logo.svg';
        $leftIcon = $actual_link .'/'. 'Images/left-side.svg';
        $rightIcon = $actual_link .'/'. 'Images/right-side.svg';
    ?>

    <head>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,500">
        <title>Bagisto Installer</title>
        <link rel="icon" sizes="16x16" href="Images/favicon.ico">
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
        <link rel="stylesheet" type="text/css" href= "<?php echo $cssUrl; ?> ">
    </head>

    <body>

        <div class="container requirement">
            <div class="initial-display" style="padding-top: 100px;">
                <img class="logo" src= "<?php echo $logo; ?>" >
            </div>

            <div class="footer">
                <img class="left-patern"  src= "<?php echo $leftIcon; ?>" >
                <img class="right-patern"  src= "<?php echo $rightIcon; ?>" >
            </div>
        </div>

        <?php
            // getting env file
            $location = str_replace('\\', '/', getcwd());
            $currentLocation = explode("/", $location);
            array_pop($currentLocation);
            array_pop($currentLocation);
            $desiredLocation = implode("/", $currentLocation);
            $envFile = $desiredLocation . '/' . '.env';

            $installed = false;

            if (file_exists($envFile)) {

                // reading env content
                $str= file_get_contents($envFile);

                // converting env content to key/value pair
                $data = explode(PHP_EOL,$str);
                $databaseArray = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CONNECTION','DB_PORT'];

                $key = $value = [];
                if ($data) {
                    foreach ($data as $line) {
                        $rowValues = explode('=', $line);
                        if (count($rowValues) === 2) {
                            if (in_array($rowValues[0], $databaseArray)) {
                                $key[] = $rowValues[0];
                                $value[] = $rowValues[1];
                            }
                        }
                    }
                }

                $databaseData['DB_HOST'] = $databaseData['DB_USERNAME'] = $databaseData['DB_PASSWORD'] =  $databaseData['DB_DATABASE'] = $databaseData['DB_CONNECTION'] = $databaseData['DB_PORT'] = '';

                $databaseData = array_combine($key, $value);

                // getting database info
                $servername = $databaseData['DB_HOST'];
                $username   = $databaseData['DB_USERNAME'];
                $password   = $databaseData['DB_PASSWORD'];
                $dbname     = $databaseData['DB_DATABASE'];
                $connection = $databaseData['DB_CONNECTION'];
                $port       = $databaseData['DB_PORT'];

                if ($connection == 'mysql') {
                    @$conn = new mysqli($servername, $username, $password, $dbname, $port);

                    if (!$conn->connect_error) {
                        // retrieving admin entry
                        $sql = "SELECT id, name FROM admins";
                        $result = $conn->query($sql);

                        if ($result) {
                            $installed = true;
                        }
                    }

                    $conn->close();
                } else {
                    $installed = true;
                }
            }

            if (!$installed) {

                // including classes
                include __DIR__ . '/Classes/Requirement.php';

                // including php files
                include __DIR__ . '/Environment.php';
                include __DIR__ . '/Migration.php';
                include __DIR__ . '/Admin.php';
                include __DIR__ . '/Finish.php';

                // including js
                include __DIR__ . '/JS/script';

                // object creation
                $requirement = new Requirement();
                echo $requirement->render();

                $storage_output = exec('cd ../.. && php artisan storage:link 2>&1');
            } else {
                // getting url
                $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

                $url = explode("/", $actual_link);
                array_pop($url);
                array_pop($url);
                $url = implode("/", $url);
                $url = $url . '/404';

                // redirecting to 404 error page
                header("Location: $url");
            }
        ?>

    </body>
</html>
