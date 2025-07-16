<?php 

/**
 * 包含已安装的模块
 */
include_installed_modules();
function include_installed_modules(){
    $module_info = get_all_modules();
    if ($module_info) {
        foreach ($module_info as $file) {
            //判断文件中是否有 $modules_info 变量 
            $content = file_get_contents($file);  
            if (strpos($content, "\$module_info") === false) {
                require $file;
            }else{ 
                //判断模块是否已安装
                $path = substr($file, strlen(PATH));
                $path = get_dir($path);
                $name = basename(dirname($path)); 
                $module = db_get_one("module", "id", ['name' => $name,'status'=>1]); 
                if ($module) {
                    require $file;
                }
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