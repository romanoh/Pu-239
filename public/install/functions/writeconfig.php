<?php
$foo = [
    'Database' => [
        [
            'text' => 'Host',
            'input' => 'config[mysql_host]',
            'info' => 'Usually this will be localhost unless your on a cluster server.',
            'placeholder' => 'localhost',
        ],
        [
            'text' => 'Username',
            'input' => 'config[mysql_user]',
            'info' => 'Your mysql username.',
            'placeholder' => 'mydbuser',
        ],
        [
            'text' => 'Password',
            'input' => 'config[mysql_pass]',
            'info' => 'Your mysql password.',
            'placeholder' => 'secret',
        ],
        [
            'text' => 'Database',
            'input' => 'config[mysql_db]',
            'info' => 'Your mysql database name.',
            'placeholder' => 'pu239',
        ],
        [
            'text' => 'Domain',
            'input' => 'announce[baseurl]',
            'info' => 'Your domain name - note include http and www.',
            'placeholder' => 'http://Pu-239.pw',
        ],
    ],
    'Tracker' => [
        [
            'text' => 'Announce Url',
            'input' => 'config[announce_urls]',
            'info' => 'Your announce url. ie. http://pu-239.pw/announce.php',
            'placeholder' => 'http://Pu-239.pw/announce.pw',
        ],
        [
            'text' => 'HTTPS Announce Url',
            'input' => 'config[announce_https]',
            'info' => 'Your HTTPS announce url. ie. https://pu-239.pw/announce.php',
            'placeholder' => 'https://Pu-239.pw/announce.pw',
        ],
        [
            'text' => 'Site Email',
            'input' => 'config[site_email]',
            'info' => 'Your site email address.',
            'placeholder' => 'myuser@mymail.com',
        ],
        [
            'text' => 'Site Name',
            'input' => 'config[site_name]',
            'info' => 'Your site name.',
            'placeholder' => 'Crafty',
        ],
        /*
                [
                    'text'  => 'Using XBT Tracker',
                    'input' => 'config[xbt_tracker]',
                    'info'  => 'Check if yes.',
                    'placeholder' => 'checked',
                ],
        */
    ],
    'Cookies' => [
        [
            'text' => 'Session Name',
            'input' => 'config[sessionName]',
            'info' => 'A single word that uniquely identifies this install.',
            'placeholder' => 'pu239',
        ],
        [
            'text' => 'Prefix',
            'input' => 'config[cookie_prefix]',
            'info' => 'A single word that uniquely identifies this install. Can be the same as Session Name, but not required.',
            'placeholder' => 'pu239',
        ],
        [
            'text' => 'Path',
            'input' => 'config[cookie_path]',
            'info' => 'Required \'/\' or any other path.',
            'placeholder' => '/',
        ],
        [
            'text' => 'Cookie Lifetime',
            'input' => 'config[cookie_lifetime]',
            'info' => 'The number of days that the cookie is alive.',
            'placeholder' => '365',
        ],
        [
            'text' => 'Cookie Domain',
            'input' => 'config[cookie_domain]',
            'info' => 'Your domain name - note exclude http and www.',
            'placeholder' => 'Pu-239.pw',
        ],
        [
            'text' => 'Domain',
            'input' => 'config[domain]',
            'info' => 'Your site domain name - note exclude http or www.',
            'placeholder' => 'Pu-239.pw',
        ],
        [
            'text' => 'Secure Session Cookies',
            'input' => 'config[sessionCookieSecure]',
            'info' => 'true/false/null. Enabled, this requires that session cookies can only be passed using SSL/HTTPS protocals.',
            'placeholder' => 'null',
        ],
    ],
    'System - Site BOT' => [
        [
            'text' => 'Username',
            'input' => 'config[bot_username]',
            'info' => "The name for your 'System' user/Site BOT.",
            'placeholder' => 'CraftyBOT',
        ],
    ],
];
function foo($x)
{
    return '/\#' . $x . '/';
}

function createblock($fo, $foo)
{
    $out = '
    <fieldset>
        <legend>' . $fo . '</legend>
        <table align="left">';
    foreach ($foo as $bo) {
        $out .= '
            <tr>
                <td class="input_text">' . $bo['text'] . '</td>';
        if (strpos($bo['input'], 'pass') == true) {
            $type = 'password';
        } elseif ($bo['input'] == 'config[xbt_tracker]') {
            $type = 'checkbox" value="yes"';
        } else {
            $type = 'text';
        }
        $out .= "
                <td class='input_input'>
                    <input type='{$type}' name='{$bo['input']}' size='30' placeholder='{$bo['placeholder']}' title='{$bo['info']}' />
                </td>
            </tr>";
    }
    $out .= '
        </table>
    </fieldset>';

    return $out;
}

function saveconfig()
{
    global $root;

    $continue = true;
    $out = '
    <fieldset>
        <legend>Write config</legend>';

    $file = '../../.env.example';
    if (file_exists($file)) {
        $env = file_get_contents($file);
        $keys = array_map('foo', array_keys($_POST['config']));
        $values = array_values($_POST['config']);
        $env = preg_replace($keys, $values, $env);
        if (file_put_contents('../../.env', $env)) {
            $out .= '
        <div class="readable">.env file was created</div>';
        } else {
            $out .= '
        <div class="notreadable">.env file could not be saved</div>';
            $continue = false;
        }
    }

    if (isset($_POST['config']['xbt_tracker'])) {
        $file = 'extra/config.xbtsample.php';
        $xbt = 1;
    } else {
        $file = 'extra/config.phpsample.php';
        $xbt = 0;
    }

    $config = file_get_contents($file);
    $keys = array_map('foo', array_keys($_POST['config']));
    $values = array_values($_POST['config']);
    $config = preg_replace($keys, $values, $config);
    $config = preg_replace('/#pass1/', bin2hex(random_bytes(16)), $config);
    $config = preg_replace('/#pass2/', bin2hex(random_bytes(16)), $config);
    $config = preg_replace('/#pass3/', bin2hex(random_bytes(16)), $config);


    if (file_put_contents($root . 'include/config.php', $config)) {
        $out .= '
        <div class="readable">config.php file was created</div>';
    } else {
        $out .= '
        <div class="notreadable">config.php file could not be saved</div>';
        $continue = false;
    }

    $file = 'extra/ann_config.phpsample.php';
    $xbt = 0;
    if (isset($_POST['config']['xbt_tracker'])) {
        $file = 'extra/ann_config.xbtsample.php';
        $xbt = 1;
    }
    $announce = file_get_contents($file);
    $keys = array_map('foo', array_keys($_POST['announce']));
    $values = array_values($_POST['announce']);
    $announce = preg_replace($keys, $values, $announce);
    if (file_put_contents($root . 'include/ann_config.php', $announce)) {
        $out .= '
        <div class="readable">ann_config.php file was created</div>';
    } else {
        $out .= '
        <div class="notreadable">ann_config.php file could not be saved</div>';
        $continue = false;
    }

    if ($continue) {
        $xbt = 0;
        if (isset($_POST['config']['xbt_tracker'])) {
            $xbt = 1;
        }
        $out .= '
        </fieldset>
        <div style="text-align:center">
            <input type="button" value="Next step" onclick="onClick(5)" />
        </div>';
    } else {
        $out .= '
        </fieldset>
        <div style="text-align:center" class="info">
            <input type="button" value="Go back" onclick="window.go(-1)"/>
        </div>';
    }

    $out .= '
    <script>
        localStorage.setItem("step", 5);
        var processing = 5;
    </script>';

    echo $out;
}
