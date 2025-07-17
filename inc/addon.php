<?php

/**
 * 视图及验证相关扩展函数
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */
/**
 * 视图
 */
function view($file, $params = [])
{
    $route = Route::getActions();
    $module = $route['module'];
    $controller = $route['controller'];
    $action = $route['action'];
    $files = [];
    $files[] = PATH . '/app/' . $module . '/view/' . $controller . '/' . $file . '.php';
    $files[] = PATH . '/app/' . $module . '/view/' . $file . '.php';
    $files[] = PATH . '/modules/' . $module . '/view/' . $controller . '/' . $file . '.php';
    $files[] = PATH . '/modules/' . $module . '/view/' . $file . '.php';
    try {
        $composer_module_path = db_get_one("module", "path", ['name' => $module]);
        if ($composer_module_path) {
            $files[] = PATH . $composer_module_path . '/view/' . $controller . '/' . $file . '.php';
            $files[] = PATH . $composer_module_path . '/view/' . $file . '.php';
        }
    } catch (\Throwable $th) {
    }
    $fined_file = find_files($files, true);
    if (!$fined_file) {
        return false;
    }
    if ($params) {
        extract($params);
    }
    ob_start();
    include $fined_file;
    $data = ob_get_clean();
    $data = trim($data);
    do_action("view." . $module . '.' . $controller . '.' . $action, $data);
    return $data;
}
/**
 * 视图头
 */
function view_header($title)
{
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php
        do_action('header');
        ?>
        <title><?= $title ?></title>
    </head>

    <body>
        <div id='app' v-cloak>
        <?php
    }
    /**
     * 视图页脚
     */
    function view_footer()
    {
        global $vue;
        ?>
        </div>

        <?php
        do_action('footer');

        ?>
        <?php if ($vue) { ?>
            <script>
                <?php

                echo $vue->run();
                ?>
            </script>
        <?php } ?>
    </body>

    </html>
<?php
    }


    /**
     * 添加简单手机号验证 
     */
    \Valitron\Validator::addRule('phonech', function ($field, $value, array $params, array $fields) {
        if (preg_match('/^1\d{10}$/', $value)) {
            return true;
        } else {
            return false;
        }
    }, '格式错误');
