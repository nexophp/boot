<?php

/**
 * 超简化的菜单类，使用数组存储，仅支持两级菜单
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

class Menu
{
    /**
     * 静态数组存储菜单
     */
    private static $menu = [];

    /**
     * 默认分组
     */
    private static $group = 'admin';

    /**
     * 添加菜单
     * @param string $name 菜单唯一标识
     * @param string $title 菜单名称
     * @param string $url 菜单链接
     * @param string $icon 菜单图标
     * @param int $sort 菜单排序，值越大越靠前
     * @param string $parent_name 父菜单唯一标识（为空表示顶级菜单）
     * @return int|bool 成功返回菜单ID，失败返回false
     */
    public static function add($name, $title, $url = '', $icon = '', $sort = 0, $parent_name = '')
    {
        // 防止重复添加
        if (isset(self::$menu[self::$group][$name])) {
            return false;
        }

        // 初始化分组
        if (!isset(self::$menu[self::$group])) {
            self::$menu[self::$group] = [];
        }

        // 生成新菜单ID
        $id = count(self::$menu[self::$group]) + 1;

        // 处理子菜单
        if ($parent_name) {
            // 检查父菜单是否存在且是顶级菜单
            if (!isset(self::$menu[self::$group][$parent_name]) || self::$menu[self::$group][$parent_name]['pid'] !== 0) {
                return false;
            }
            self::$menu[self::$group][$name] = [
                'id' => $id,
                'name' => $name,
                'title' => $title,
                'url' => $url,
                'icon' => $icon,
                'sort' => $sort,
                'pid' => self::$menu[self::$group][$parent_name]['id']
            ];
            return $id;
        }

        // 添加顶级菜单
        self::$menu[self::$group][$name] = [
            'id' => $id,
            'name' => $name,
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'sort' => $sort,
            'pid' => 0
        ];

        return $id;
    }

    /**
     * 获取两级菜单树结构
     * @param int $pid 父级ID（默认0表示从顶级开始）
     * @return array 菜单树
     */
    public static function get($pid = 0)
    {
        // 如果分组不存在，返回空数组
        if (!isset(self::$menu[self::$group])) {
            return [];
        }

        // 按sort降序和id升序排序
        $sortedMenus = self::$menu[self::$group];
        uasort($sortedMenus, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return $a['id'] <=> $b['id'];
            }
            return $b['sort'] <=> $a['sort'];
        });

        // 构建两级菜单树
        $tree = [];
        foreach ($sortedMenus as $menu) {
            if ($menu['pid'] == $pid) {
                if ($pid == 0) {
                    // 为顶级菜单添加子菜单
                    $children = array_filter($sortedMenus, fn($m) => $m['pid'] == $menu['id']);
                    if ($children) {
                        uasort($children, fn($a, $b) => $b['sort'] <=> $a['sort'] ?: $a['id'] <=> $b['id']);
                        $menu['children'] = array_values($children);
                    }
                }
                $tree[] = $menu;
            }
        }

        return $tree;
    }

    /**
     * 设置当前分组
     * @param string $group 分组名称
     */
    public static function setGroup($group)
    {
        self::$group = $group;
    }
}