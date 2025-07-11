<?php

/**
 *  分页 
 *  类似淘宝分页
 *  　　 
 * @since 2014-2015
 */
/**
 *<code>
 *类似淘宝分页 
 *	
 *	 
 *$paginate = new lib\Paginate($row->num,1); 
 *$paginate->url = $this->url;
 *$limit = $paginate->limit;
 *$offset = $paginate->offset;
 *	
 *$paginate = $paginate->show(); 
 * 
 * 
.pagination li{
    list-style: none;
    float: left;
    display: inline-block;
    border: 1px solid #ff523b;
    margin-left: 10px;
    width: 40px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    cursor: pointer;
}
.pagination .active{
   background: #eee; 
   border: 1px solid #000;
}

 *</code>	
 *
 */

namespace lib;

class Paginate
{
	public $page;
	public $pages;
	public $url;
	public $size;
	public $count;
	public $limit;
	public $offset;
	public $get = [];
	static $class;
	public $query = 'page';
	/**
	 * 构造函数  
	 */
	public function __construct($count, $size = 10)
	{
		$this->count = $count;
		$this->size = $size;
		//总页数
		$this->pages = ceil($this->count / $this->size);
		//当前页面
		$this->page = (int)$_GET[$this->query];
		if ($this->pages < 1) return;
		if ($this->page <= 1)
			$this->page = 1;
		if ($this->page >= $this->pages)
			$this->page = $this->pages;

		$this->offset = $this->size * ($this->page - 1);
		$this->limit = $this->size;
	}
	/**
	 * 生成URL函数，如有需要，可自行改写
	 * 调用函数 ($url,$par);
	 * @param string $url 　 
	 * @param string $par 　 
	 * @return  string
	 */
	public function url($url, $par = [])
	{
		$url = $url . '?' . http_build_query($par);
		return $url;
	}
	public function next($class = 'pagination')
	{
		$next = $this->page + 1;
		$p = $_GET;
		$p[$this->query] = $next;
		if ($next <= $this->pages) {
			return '<a rel="' . $next . '" class="' . $class . '" href="' . $this->url($this->url, $p) . '">下一页</a>';
		}
		return;
	}

	/**
	 * 显示分页 pagination
	 * @param string $class 　 
	 * @return  string
	 */
	public function show($class = 'pagination')
	{
		if (static::$class) $class = static::$class;
		$str = '<ul class="' . $class . '">';
		$pre = $this->page - 1;
		$p = $_GET;
		$p[$this->query] = $pre > 0 ? $pre : 1;
		if ($pre > 0)
			$str .= '<li><a href="' . $this->url($this->url, $p) . '">&laquo;</a></li>';
		if ($this->pages < 2) return;
		$pages[1] = 1;
		$pages[2] = 2;
		$i = $this->page - 2 <= 1 ? 1 : $this->page - 2;
		$e = $this->page + 2 >= $this->pages ? $this->pages : $this->page + 2;
		if ($e < 5 && $this->pages >= 5)
			$e = 5;
		$pages['s'] = null;
		if ($i > 0) {
			for ($i; $i < $e + 1; $i++) {
				$pages[$i] = $i;
			}
		}
		$j = 0;
		foreach ($pages as $k => $v) {
			if ($j == 3) $n = $k;
			$j++;
		}

		if ($this->pages > 5) {
			if ($n != 3)
				$pages['s'] = "...";
			if ($e < $this->pages)
				$pages['e'] = "...";
		}
		$p = $_GET;
		if ($this->get) {
			foreach ($this->get as $d) {
				unset($p[$d]);
			}
		}

		foreach ($pages as $j) {
			$active = null;
			if ($j == $this->page)
				$active = "class='active'";
			if (!$j) continue;
			$p[$this->query] = $j;
			if ($j == '...')
				$str .= "<li $active><a href='javascript:void(0);' class='no'>$j</a></li>";
			else
				$str .= "<li $active><a href='" . $this->url($this->url, $p) . "'>$j</a></li>";
		}

		if ($this->page + 3 < $this->pages && $this->pages > 6) {
			$str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages - 1] + $p) . "'>" . ($this->pages - 1) . "</a></li>";
		}
		if ($this->page + 2 < $this->pages && $this->pages > 6) {
			$str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages] + $p) . "'>$this->pages</a></li>";
		}
		$p[$this->query] = $next = $this->page + 1;
		if ($next <= $this->pages)
			$str .= '<li><a href="' . $this->url($this->url, $p) . '">&raquo;</a></li>';


		$str .= "</ul>";
		return $str;
	}
}
