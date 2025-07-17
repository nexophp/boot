<?php

/**
 * 菜单数据库操作类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

class Menu extends AppModel
{
    /**
     * 表名
     */
    protected $table = 'menu';

    /**
     * 添加菜单
     * @param string $name 菜单唯一标识
     * @param string $title 菜单名称
     * @param string $url 路由地址
     * @param int $pid 父级ID，默认为0表示顶级菜单
     * @param string $icon 菜单图标，例如 'bi bi-house'
     * @param int $sort 排序值，值越大越靠前
     * @return int|bool 成功返回插入ID，失败返回false
     */
    public function add($name, $title, $url = '', $pid = 0, $icon = '', $sort = 0)
    {
        // 检查name是否已存在
        if ($this->getByName($name)) {
            return false;
        }

        $data = [
            'name' => $name,
            'title' => $title,
            'url' => $url,
            'pid' => intval($pid),
            'level' => $pid > 0 ? $this->getLevel($pid) + 1 : 1,
            'icon' => $icon,
            'sort' => intval($sort)
        ];

        return db_insert($this->table, $data);
    }

    /**
     * 更新菜单
     * @param int $id 菜单ID
     * @param array $data 更新数据
     * @return bool 更新结果
     */
    public function update($id, $data)
    {
        // 如果更新了pid，需要重新计算level
        if (isset($data['pid'])) {
            $data['level'] = $data['pid'] > 0 ? $this->getLevel($data['pid']) + 1 : 1;
        }

        // 如果包含name字段，检查唯一性
        if (isset($data['name'])) {
            $exists = db_get_one($this->table, '*', ['name' => $data['name'], 'id[!]' => $id]);
            if ($exists) {
                return false;
            }
        }

        return db_update($this->table, $data, ['id' => $id]);
    }

    /**
     * 删除菜单
     * @param int $id 菜单ID
     * @return bool 删除结果
     */
    public function delete($id)
    {
        // 先删除所有子菜单
        $children = $this->getChildren($id);
        foreach ($children as $child) {
            db_del($this->table, ['id' => $child['id']]);
        }

        // 删除当前菜单
        return db_del($this->table, ['id' => $id]);
    }

    /**
     * 根据ID获取菜单
     * @param int $id 菜单ID
     * @return array|null 菜单信息
     */
    public function getById($id)
    {
        return db_get_one($this->table, '*', ['id' => $id]);
    }

    /**
     * 根据name获取菜单
     * @param string $name 菜单唯一标识
     * @return array|null 菜单信息
     */
    public function getByName($name)
    {
        return db_get_one($this->table, '*', ['name' => $name]);
    }

    /**
     * 获取菜单级别
     * @param int $id 菜单ID
     * @return int 菜单级别
     */
    public function getLevel($id)
    {
        $menu = $this->getById($id);
        return $menu ? intval($menu['level']) : 0;
    }

    /**
     * 获取所有菜单
     * @param array $where 查询条件
     * @return array 菜单列表
     */
    public function getAll($where = [])
    {
        // 默认按sort降序、id升序排列
        if (!isset($where['ORDER'])) {
            $where['ORDER'] = ['sort' => 'DESC', 'id' => 'ASC'];
        }
        return db_get($this->table, '*', $where);
    }

    /**
     * 获取子菜单
     * @param int $pid 父级ID
     * @return array 子菜单列表
     */
    public function getChildren($pid)
    {
        return db_get($this->table, '*', ['pid' => $pid, 'ORDER' => ['sort' => 'DESC', 'id' => 'ASC']]);
    }

    /**
     * 获取树形菜单结构
     * @param int $pid 父级ID，默认0表示从顶级开始
     * @return array 树形菜单
     */
    public function getTree($pid = 0)
    {
        // 获取所有菜单，按sort降序排列
        $allMenus = $this->getAll(['ORDER' => ['sort' => 'DESC', 'level' => 'ASC', 'id' => 'ASC']]);

        // 构建树形结构
        return $this->buildTree($allMenus, $pid);
    }

    /**
     * 构建树形结构
     * @param array $menus 所有菜单
     * @param int $pid 父级ID
     * @return array 树形结构
     */
    private function buildTree($menus, $pid = 0)
    {
        $tree = [];

        foreach ($menus as $menu) {
            if ($menu['pid'] == $pid) {
                $children = $this->buildTree($menus, $menu['id']);
                if ($children) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }

        return $tree;
    }

    /**
     * 获取菜单路径（从顶级到当前菜单的路径）
     * @param int $id 菜单ID
     * @return array 菜单路径
     */
    public function getPath($id)
    {
        $path = [];
        $menu = $this->getById($id);

        if (!$menu) {
            return $path;
        }

        $path[] = $menu;

        // 递归获取父级菜单
        if ($menu['pid'] > 0) {
            $parentPath = $this->getPath($menu['pid']);
            $path = array_merge($parentPath, $path);
        }

        return $path;
    }

    /**
     * 移动菜单（修改父级）
     * @param int $id 菜单ID
     * @param int $pid 新的父级ID
     * @return bool 移动结果
     */
    public function move($id, $pid)
    {
        // 不能将菜单移动到自己或其子菜单下
        if ($id == $pid) {
            return false;
        }

        // 检查是否是将菜单移动到其子菜单下
        $children = $this->getChildren($id);
        foreach ($children as $child) {
            if ($child['id'] == $pid) {
                return false;
            }
        }

        // 计算新的level
        $level = $pid > 0 ? $this->getLevel($pid) + 1 : 1;

        // 更新当前菜单
        $result = $this->update($id, ['pid' => $pid, 'level' => $level]);

        // 更新所有子菜单的level
        if ($result) {
            $this->updateChildrenLevel($id, $level);
        }

        return $result;
    }

    /**
     * 更新子菜单的level
     * @param int $pid 父级ID
     * @param int $parentLevel 父级level
     */
    private function updateChildrenLevel($pid, $parentLevel)
    {
        $children = $this->getChildren($pid);

        foreach ($children as $child) {
            $newLevel = $parentLevel + 1;
            db_update($this->table, ['level' => $newLevel], ['id' => $child['id']]);

            // 递归更新子菜单
            $this->updateChildrenLevel($child['id'], $newLevel);
        }
    }

    /**
     * 更新菜单排序
     * @param int $id 菜单ID
     * @param int $sort 排序值，值越大排列越靠前
     * @return bool 更新结果
     */
    public function updateSort($id, $sort)
    {
        return $this->update($id, ['sort' => intval($sort)]);
    }

    /**
     * 批量更新菜单排序
     * @param array $sortData 排序数据，格式：[['id' => 1, 'sort' => 100], ['id' => 2, 'sort' => 90]...]
     * @return bool 更新结果
     */
    public function batchUpdateSort($sortData)
    {
        if (empty($sortData) || !is_array($sortData)) {
            return false;
        }

        $result = true;
        foreach ($sortData as $item) {
            if (isset($item['id']) && isset($item['sort'])) {
                $updateResult = $this->updateSort($item['id'], $item['sort']);
                if (!$updateResult) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}
