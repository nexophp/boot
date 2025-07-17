<?php

/**
 * 权限管理类
 * @author sunkangchina <68103403@qq.com>
 * @date 2025
 */

namespace core;

use ReflectionMethod;
use ReflectionClass;
use Exception;


class Permission
{
    /**
     * 必须是继承了哪些类
     */
    public static $hasExtendsClasses = [
        '\core\AppController',
        '\core\AdminController'
    ];

    /**
     * 扫描系统中所有的权限注解
     * @return array 权限列表
     */
    public function scanPermissions(): array
    {
        $permissions = [];
        $all = get_all_modules();
        $modules = [];
        foreach ($all as $v) {
            $modules[] = get_dir($v);
        }
        foreach ($modules as $modulePath) {
            $controllerPath = $modulePath . '/controller';
            $controllers = glob($controllerPath . '/*Controller.php');
            foreach ($controllers as $controller) {
                $className = $this->getClassNameFromFile($controller);
                if (!$className) continue;

                try {
                    $reflectionClass = new ReflectionClass($className);
                    $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        if (strpos($method->getName(), 'action') === 0) {
                            $docComment = $method->getDocComment();
                            if ($docComment && preg_match('/@permission\s+([\w\.\p{Han}\s]+)/u', $docComment, $matches)) {
                                $permissionStr = trim($matches[1]);
                                $permissionList = array_filter(array_map('trim', explode(' ', $permissionStr)));

                                foreach ($permissionList as $permission) {
                                    $module = basename($modulePath);
                                    $controllerName = str_replace('Controller', '', basename($controller, '.php'));
                                    $actionName = str_replace('action', '', $method->getName());
                                    $controllerName = \Route::toUrlFriendly($controllerName);
                                    $actionName = \Route::toUrlFriendly($actionName);
                                    // 解析权限组和权限名称
                                    $permParts = explode('.', $permission);
                                    $permGroup = $permParts[0];
                                    $permName = $permParts[1] ?? '';

                                    $path = $module . '/' . $controllerName . '/' . $actionName;

                                    // 以permission_name为唯一键
                                    if (!isset($permissions[$permission])) {
                                        $permissions[$permission] = [
                                            'name' => $permission,
                                            'group' => $permGroup,
                                            'permission' => $permName,
                                            'module' => $module,
                                            'controller' => $controllerName,
                                            'action' => $actionName,
                                            'paths' => [$path],
                                            'description' => $this->getDescriptionFromDocComment($docComment)
                                        ];
                                    } else {
                                        // 如果已存在相同权限名称，则将path添加到paths数组中
                                        if (!in_array($path, $permissions[$permission]['paths'])) {
                                            $permissions[$permission]['paths'][] = $path;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // 忽略无法反射的类
                    continue;
                }
            }
        }

        // 将关联数组转换为索引数组
        return array_values($permissions);
    }

    /**
     * 从文件路径获取完整的类名
     * @param string $filePath 文件路径
     * @param string $moduleType 模块类型 (modules 或 app)
     * @return string|null 类名
     */
    protected function getClassNameFromFile($filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = $matches[1];
            $className = $namespace . '\\' . basename($filePath, '.php');
            //需要判断 是否有 self::$hasExtendsClasses 
            $flag = false;
            foreach (self::$hasExtendsClasses as $v) {
                if (strpos($content, $v) !== false) {
                    $flag = true;
                    break;
                }
            }
            if ($flag) {
                return $className;
            }
        }
        return null;
    }

    /**
     * 从文档注释中提取描述信息
     * @param string $docComment 文档注释
     * @return string 描述信息
     */
    protected function getDescriptionFromDocComment($docComment): string
    {
        $description = '';
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim($line, "/* \t\n\r\0\x0B");
            if (!empty($line) && strpos($line, '@') !== 0) {
                $description = $line;
                break;
            }
        }
        return $description;
    }
}
