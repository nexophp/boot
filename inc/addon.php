<?php

/**
 * addon
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

/**
 * 权限验证 同 has_access
 */
function if_access($str)
{
    return has_access($str);
}
/**
 * 判断是否有权限
 */
function has_access($str)
{
    static $permissions;
    $uid = cookie('uid');
    if (!$uid) {
        return false;
    }
    if (!$str) {
        return false;
    }
    if (substr($str, 0, 1) == '/') {
        $str = substr($str, 1);
    }
    //超管不用判断权限
    if ($uid == 1) {
        return true;
    }
    if (!$permissions) {
        $role_id = db_get("user_role", "role_id", ['user_id' => $uid]);
        if ($role_id) {
            $roles = db_get("role", "*", ['id' => $role_id]);
            $permissions = [];
            if ($roles) {
                foreach ($roles as $v) {
                    $permissions = array_merge($permissions, $v['permissions'] ?? []);
                }
            }
        }
    }
    if ($permissions) {
        if (in_array($str, $permissions)) {
            return true;
        }
    }
}

/**
 * 包含已安装的模块
 */
include_installed_modules();
function include_installed_modules()
{
    $module_info = get_all_modules();
    if ($module_info) {
        foreach ($module_info as $file) {
            $name = get_module_name($file);
            if ($name) {
                $module = db_get_one("module", "id", ['name' => $name, 'status' => 1]);
                if ($module) {
                    require $file;
                }
            } else {
                include $file;
            }
        }
    }
}

/**
 * 加载模块下的nexophp.php
 */
function get_all_modules()
{
    $vendor_list = glob(PATH . '/vendor/*/*/src/nexophp.php');
    $modules_list = glob(PATH . '/modules/*/nexophp.php');
    $app_list = glob(PATH . '/app/*/nexophp.php');
    $list = array_merge($vendor_list, $modules_list, $app_list);
    return $list;
}
/**
 * 获取模块名称
 * 先调用 get_all_modules 
 */
function get_module_name($file, $ignore_noe_module = true)
{
    $content = file_get_contents($file);
    if ($ignore_noe_module && strpos($content, "\$module_info") === false) {
        return;
    }
    $path = substr($file, strlen(PATH));
    $path = get_dir($path);
    if (substr($path, -4) == '/src') {
        $path = substr($path, 0, -4);
    }
    return substr($path, strrpos($path, '/') + 1);
}
/**
 * 获取模块路径
 */
function get_module_path($file)
{
    $path = substr($file, strlen(PATH));
    $path = get_dir($path);
    return $path;
}
/**
 * 视图
 */
function view($file, $params = [])
{
    $route = Route::getActions();
    $module = $route['module'];
    $controller = $route['controller'];
    $action = $route['action'];
    $controller = Route::toUrlFriendly($controller);
    $file  = Route::toUrlFriendly($file);
    $files = [];
    $files[] = PATH . '/app/' . $module . '/view/' . $controller . '/' . $file . '.php';
    $files[] = PATH . '/app/' . $module . '/view/' . $file . '.php';
    $files[] = PATH . '/modules/' . $module . '/view/' . $controller . '/' . $file . '.php';
    $files[] = PATH . '/modules/' . $module . '/view/' . $file . '.php';
    $all = get_all_modules();
    $name = [];
    $current_module_path = '';
    foreach ($all as $v) {
        $module_name = get_module_name($v, false);
        if ($module_name == $module) {
            $current_module_path = get_module_path($v);
        }
    }
    $files[] = PATH . $current_module_path . '/view/' . $controller . '/' . $file . '.php';
    $files[] = PATH . $current_module_path . '/view/' . $file . '.php';
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
