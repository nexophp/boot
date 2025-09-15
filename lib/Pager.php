<?php

namespace lib;

class Pager
{
    /**
     * 分页渲染
     * @param string $url 分页url
     * @param int $page 当前页
     * @param int $last_page 最后一页
     */
    public  static function render($url, $page, $last_page)
    {

        // 验证输入
        $page = isset($page) && is_numeric($page) ? max(1, min($last_page, (int)$page)) : 1; // 确保当前页在1到最后一页之间
        $last_page = max(1, (int)$last_page); // 确保总页数至少为1

        // 分页设置
        $max_visible_pages = 5; // 最大显示的页面按钮数量
        $half_visible = floor($max_visible_pages / 2); // 计算显示范围的中间点
        $start_page = max(1, $page - $half_visible); // 计算起始页，确保最小为1
        $end_page = min($last_page, $start_page + $max_visible_pages - 1); // 计算结束页

        // 如果接近末尾，调整起始页
        if ($end_page - $start_page + 1 < $max_visible_pages) {
            $start_page = max(1, $end_page - $max_visible_pages + 1); // 确保起始页最小为1
        }
?>

        <div class="pagination">
            <!-- 上一页按钮 -->
            <a class="page-btn prev<?php echo $page <= 1 ? ' disabled' : ''; ?>"
                <?php echo $page > 1 ? 'href="' . self::url($url, $page - 1) . '"' : ''; ?>>
                <?= lang('上一页') ?>
            </a>

            <?php
            // 显示第一页
            if ($start_page > 1) {
                echo '<a class="page-btn' . ($page == 1 ? ' active' : '') . '" href="' . self::url($url, 1) . '">1</a>';
                if ($start_page > 2) {
                    echo '<span class="page-dots">...</span>'; // 显示省略号
                }
            }

            // 显示页面编号
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<a class="page-btn' . ($page == $i ? ' active' : '') . '" href="' . self::url($url, $i) . '">' . $i . '</a>';
            }

            // 显示最后一页
            if ($end_page < $last_page) {
                if ($end_page < $last_page - 1) {
                    echo '<span class="page-dots">...</span>'; // 显示省略号
                }
                echo '<a class="page-btn' . ($page == $last_page ? ' active' : '') . '" href="' . self::url($url, $last_page) . '">' . $last_page . '</a>';
            }
            ?>

            <!-- 下一页按钮 -->
            <a class="page-btn next<?php echo $page >= $last_page ? ' disabled' : ''; ?>"
                <?php echo $page < $last_page ? 'href="' . self::url($url, $page + 1) . '"' : ''; ?>>
                <?= lang('下一页') ?>
            </a>
        </div>
<?php
    }

    /**
     * 分页url
     * @param int $page 页码
     * @return string
     */
    public static function url($url, $page)
    {
        $par = [];
        if ($_GET) {
            foreach ($_GET as $k => $v) {
                if ($k != 'page' && substr($k, 0, 1) != '_') {
                    $par[$k] = $v;
                }
            }
        }
        $par['page'] = $page;
        return url($url, $par);
    }
}
