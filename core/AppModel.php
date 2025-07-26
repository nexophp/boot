<?php

/**
 * 应用模型基类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

class AppModel extends \DbModel implements \ArrayAccess
{
    /**
     * 用户ID
     * @var mixed
     */
    protected $user_id;

    /**
     * 用户唯一标识
     * @var mixed
     */
    protected $uid;

    /**
     * 存储数据的数组
     * @var array
     */
    protected $data = [];

    /**
     * 初始化方法，设置用户ID
     */
    protected function init()
    {
        global $uid;
        $this->user_id = $this->uid = $uid;
    }

    /**
     * ArrayAccess 接口：检查偏移量是否存在
     * @param mixed $offset 偏移量
     * @return bool 是否存在
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess 接口：获取偏移量对应的值
     * @param mixed $offset 偏移量
     * @return mixed 值
     */
    public function offsetGet($offset): mixed
    {
        // 尝试多种命名格式调用 getAttrXxx 方法
        $method = $this->resolveGetterMethod($offset);
        if ($method && method_exists($this, $method)) {
            return $this->$method();
        }
        // 回退到 data 数组
        return $this->data[$offset] ?? null;
    }

    /**
     * ArrayAccess 接口：设置偏移量对应的值
     * @param mixed $offset 偏移量
     * @param mixed $value 值
     */
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess 接口：删除偏移量对应的值
     * @param mixed $offset 偏移量
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * 魔术方法：获取属性值
     * @param string $name 属性名
     * @return mixed 属性值
     */
    public function __get($name)
    {
        // 尝试多种命名格式调用 getAttrXxx 方法
        $method = $this->resolveGetterMethod($name);
        if ($method && method_exists($this, $method)) {
            return $this->$method();
        }
        // 回退到 data 数组
        return $this->data[$name] ?? null;
    }

    /**
     * 魔术方法：设置属性值
     * @param string $name 属性名
     * @param mixed $value 属性值
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 解析属性名到 getter 方法名
     * @param string $name 属性名（如 demo_test、demotest、DemoTest）
     * @return string|null getter 方法名（如 getAttrDemoTest）
     */
    protected function resolveGetterMethod($name)
    {
        // 原始名称直接尝试
        $method = 'getAttr' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $method;
        }

        // 下划线转驼峰（demo_test -> DemoTest）
        $camelCase = str_replace('_', '', ucwords($name, '_'));
        $method = 'getAttr' . $camelCase;
        if (method_exists($this, $method)) {
            return $method;
        }

        // 驼峰格式，首字母小写（demotest -> Demotest）
        $method = 'getAttr' . ucfirst(strtolower($name));
        if (method_exists($this, $method)) {
            return $method;
        }

        return null;
    }

    /**
     * 查询记录，重写以支持对象化返回
     * @param mixed $where 查询条件
     * @param mixed $limit 限制条数
     * @param bool $use_select 是否使用原生查询
     * @param bool $ignore_hook 是否忽略钩子
     * @return array|static|null 查询结果
     */
    public function find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        $results = parent::find($where, $limit, $use_select, $ignore_hook);

        if (empty($results)) {
            return $limit == 1 ? null : [];
        }

        if ($limit == 1 && is_array($results)) {
            $model = new static();
            $model->data = $results;
            return $model;
        }

        $models = [];
        foreach ($results as $row) {
            $model = new static();
            $model->data = is_array($row) ? $row : (array)$row;
            $models[] = $model;
        }

        return $models;
    }

    /**
     * 分页查询，重写以支持对象化返回
     * @param mixed $join 关联查询条件
     * @param mixed $columns 查询字段
     * @param mixed $where 查询条件
     * @param bool $ignore_hook 是否忽略钩子
     * @return array 分页数据
     */
    public function pager($join, $columns = null, $where = null, $ignore_hook = false)
    {
        $result = parent::pager($join, $columns, $where, $ignore_hook);

        $models = [];
        foreach ($result['data'] as $row) {
            $model = new static();
            $model->data = is_array($row) ? $row : (array)$row;
            $models[] = $model;
        }

        $result['data'] = $models;
        return $result;
    }
}
