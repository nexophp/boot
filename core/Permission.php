<?php

namespace core;

use ReflectionMethod;
use Exception;

/**
 * 权限管理类
 * @author sunkangchina <68103403@qq.com>
 * @date 2025
 */
class Permission
{
    /**
     * 当前用户权限列表
     * @var array
     */
    protected $userPermissions = [];

    /**
     * 构造函数，初始化用户权限
     * @param array $userPermissions 用户权限列表，如 ['模块.管理', '模块.查看']
     */
    public function __construct(array $userPermissions = [])
    {
        $this->userPermissions = $userPermissions;
    }

    /**
     * 检查用户是否具有指定权限
     * @param string $requiredPermission 所需权限，如 "模块.管理"
     * @param string|null $method 方法名，默认为调用者的方法
     * @param string|null $class 类名，默认为调用者的类
     * @return bool
     * @throws Exception 如果无法解析权限
     */
    public function hasAccess(string $requiredPermission, ?string $method = null, ?string $class = null): bool
    {
        // 获取调用者的方法和类（如果未指定）
        if (!$method || !$class) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $method = $trace[1]['function'] ?? null;
            $class = $trace[1]['class'] ?? null;
        }

        if (!$class || !$method) {
            throw new Exception('无法确定调用者的类或方法');
        }

        // 获取方法权限
        $methodPermissions = $this->getMethodPermissions($class, $method);

        if (empty($methodPermissions)) {
            return false; // 无权限定义，拒绝访问
        }

        // 检查任一权限是否匹配
        foreach ($methodPermissions as $methodPermission) {
            if ($this->checkPermission($methodPermission, $requiredPermission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取方法的权限信息
     * @param string $class 类名
     * @param string $method 方法名
     * @return array 方法的权限列表，如 ['模块.管理', '模块.查看']
     * @throws Exception 如果反射失败
     */
    protected function getMethodPermissions(string $class, string $method): array
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            $docComment = $reflection->getDocComment();

            if (!$docComment) {
                return [];
            }

            // 提取 @permission 标签（支持多个权限，空格分隔）
            if (preg_match('/@permission\s+([\w\.\p{Han}\s]+)/u', $docComment, $matches)) {
                $permissions = array_filter(array_map('trim', explode(' ', trim($matches[1]))));
                return array_values($permissions); // 返回 ['模块.管理', '模块.查看']
            }

            return [];
        } catch (\ReflectionException $e) {
            throw new Exception('解析权限失败: ' . $e->getMessage());
        }
    }

    /**
     * 检查权限是否匹配
     * @param string $methodPermission 方法的权限，如 "模块.管理"
     * @param string $requiredPermission 所需权限，如 "模块.管理"
     * @return bool
     */
    protected function checkPermission(string $methodPermission, string $requiredPermission): bool
    {
        // 直接匹配
        if (in_array($methodPermission, $this->userPermissions)) {
            return true;
        }

        // 层级匹配（如 模块.管理 匹配 模块.管理.查看）
        if (strpos($methodPermission, $requiredPermission . '.') === 0) {
            return true;
        }

        // 通配符匹配（如 模块.* 匹配 模块.管理）
        foreach ($this->userPermissions as $perm) {
            if (strpos($perm, '*') !== false) {
                $pattern = '/^' . str_replace('*', '.*', preg_quote($perm, '/')) . '$/u';
                if (preg_match($pattern, $methodPermission)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 设置用户权限
     * @param array $permissions 权限列表
     */
    public function setUserPermissions(array $permissions): void
    {
        $this->userPermissions = $permissions;
    }

    /**
     * 获取用户权限
     * @return array
     */
    public function getUserPermissions(): array
    {
        return $this->userPermissions;
    }
}
