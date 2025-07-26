<?php

/**
 * 应用模型基类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

class AppModel extends \DbModel implements \ArrayAccess, \JsonSerializable
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
        return isset($this->data[$offset]) || $this->hasRelation($offset);
    }

    /**
     * ArrayAccess 接口：获取偏移量对应的值
     * @param mixed $offset 偏移量
     * @return mixed 值
     */
    public function offsetGet($offset): mixed
    {
        if ($this->hasRelation($offset)) {
            return $this->getRelation($offset);
        }

        $method = $this->resolveGetterMethod($offset);
        if ($method && method_exists($this, $method)) {
            $result = $this->$method();
            if ($this->isRelationConfig($result)) {
                return $this->getRelation($offset);
            }
            return $result;
        }

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
        $method = $this->resolveGetterMethod($name);
        if ($method && method_exists($this, $method)) {
            $result = $this->$method();
            if ($this->isRelationConfig($result)) {
                return $this->getRelation($name);
            }
            return $result;
        }

        if ($this->hasRelation($name)) {
            return $this->getRelation($name);
        }

        return $this->data[$name] ?? null;
    }

    /**
     * 魔术方法：设置属性值
     * @param string $name 属性名
     * @param mixed $value 值
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 检查是否为关联定义（静态或动态）
     * @param string $name 属性名
     * @return bool 是否存在关联定义
     */
    protected function hasRelation($name)
    {
        if (isset($this->has_one[$name]) || isset($this->has_many[$name])) {
            return true;
        }

        $method = $this->resolveGetterMethod($name);
        if ($method && method_exists($this, $method)) {
            $config = $this->$method();
            return $this->isRelationConfig($config);
        }

        return false;
    }

    /**
     * 检查返回值是否为关联配置
     * @param mixed $config 配置数据
     * @return bool 是否为 has_one 或 has_many 配置
     */
    protected function isRelationConfig($config)
    {
        return is_array($config) && (isset($config['has_one']) || isset($config['has_many']));
    }

    /**
     * 获取关联数据
     * @param string $name 关联字段名
     * @return mixed 关联数据
     */
    protected function getRelation($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $method = $this->resolveGetterMethod($name);
        $config = $method && method_exists($this, $method) ? $this->$method() : null;

        $isDynamic = $this->isRelationConfig($config);
        if ($isDynamic) {
            if (isset($config['has_one'])) {
                $this->has_one[$name] = $config['has_one'];
            } elseif (isset($config['has_many'])) {
                $this->has_many[$name] = $config['has_many'];
            }
        }

        $data = $this->data;
        if (!$this->ignore_relation || $isDynamic) {
            $this->doRelation($data);
            error_log("getRelation: $name, data[$name]: " . print_r($data[$name] ?? 'null', true));
        }

        if (isset($this->has_one[$name])) {
            $relationData = $data[$name] ?? null;
            if ($relationData && (is_array($relationData) || is_object($relationData))) {
                $modelClass = $this->has_one[$name][0];
                if (!class_exists($modelClass)) {
                    error_log("Relation class $modelClass not found for $name");
                    $this->data[$name] = null;
                    return null;
                }
                $model = new $modelClass();
                $model->data = is_object($relationData) ? (array)$relationData : $relationData;
                $this->data[$name] = $model;
            } else {
                $this->data[$name] = null;
            }
        } elseif (isset($this->has_many[$name])) {
            $relationData = $data[$name] ?? [];
            $models = [];
            if (is_array($relationData) || is_object($relationData)) {
                $modelClass = $this->has_many[$name][0];
                if (!class_exists($modelClass)) {
                    error_log("Relation class $modelClass not found for $name");
                    $this->data[$name] = [];
                    return [];
                }
                $relationData = is_object($relationData) ? (array)$relationData : $relationData;
                foreach ($relationData as $row) {
                    $model = new $modelClass();
                    $model->data = is_array($row) ? $row : (array)$row;
                    $models[] = $model;
                }
            }
            $this->data[$name] = $models;
        } else {
            $this->data[$name] = null;
        }

        // 清理动态关联配置
        if ($isDynamic) {
            unset($this->has_one[$name]);
            unset($this->has_many[$name]);
        }

        return $this->data[$name] ?? null;
    }

    /**
     * 解析属性名到 getter 方法名
     * @param string $name 属性名（如 demo_test、demotest、DemoTest）
     * @return string|null getter 方法名（如 getAttrDemoTest）
     */
    protected function resolveGetterMethod($name)
    {
        $method = 'getAttr' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $method;
        }

        $camelCase = str_replace('_', '', ucwords($name, '_'));
        $method = 'getAttr' . $camelCase;
        if (method_exists($this, $method)) {
            return $method;
        }

        $method = 'getAttr' . ucfirst(strtolower($name));
        if (method_exists($this, $method)) {
            return $method;
        }

        return null;
    }

    /**
     * 查询记录，重写以支持对象化返回数据
     * @param mixed $where 查询条件
     * @param mixed $limit 限制条数
     * @param bool $use_select 是否使用原生查询
     * @param bool $ignore_hook 是否忽略钩子
     * @return array|static|null 查询结果
     */
    public function find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        if (is_numeric($where)) {
            $limit = 1;
            $where = ['id' => $where];
        }
        $results = parent::find($where, $limit, $use_select, $ignore_hook);

        if (empty($results)) {
            return $limit == 1 ? null : [];
        }

        if ($limit == 1 && is_array($results)) {
            $model = new static();
            $model->data = $results;
            if (!$this->ignore_relation && !empty($this->relation)) {
                $this->doRelation($model->data);
            }
            return $model;
        }

        $models = [];
        foreach ($results as $row) {
            $model = new static();
            $model->data = is_array($row) ? $row : (array)$row;
            if (!$this->ignore_relation && !empty($this->relation)) {
                $this->doRelation($model->data);
            }
            $models[] = $model;
        }

        return $models;
    }

    /**
     * 分页查询，重写以支持对象化返回数据和关联
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
            if (!$this->ignore_relation && !empty($this->relation)) {
                $this->doRelation($model->data);
            }
            $models[] = $model;
        }

        $result['data'] = $models;
        return $result;
    }
    /**
     * 转换为 JSON 字符串
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    /**
     * 转换为数组
     */
    public function toArray()
    {
        $data = $this->data; // 只返回模型数据，不包含 protected/private 属性

        // 递归处理关联数据
        foreach ($data as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $data[$key] = array_map(function ($item) {
                    return is_object($item) && method_exists($item, 'toArray')
                        ? $item->toArray()
                        : $item;
                }, $value);
            }
        }

        return $data;
    }
}
