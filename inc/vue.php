<?php

/**
 * 多文件上传组件（带数量限制）
 * @param string $name 字段名
 * @param string $top 数据模型路径
 * @param string $mime 允许的文件类型（jpg,png等）
 * @param int $max_files 最大文件数量（0表示不限制）
 */
function vue_upload_files($name = 'files', $top = 'form', $mime = '', $max_files = 1)
{
    global $vue;

    // 生成加密的回调JS（增加数量校验）
    $js = aes_encode("
        parent.layer.closeAll();
        let currentFiles = parentVue.{$top}.{$name} || [];        
        // 数量限制检查
        if ({$max_files} > 0 && (currentFiles.length + data.length) > {$max_files}) {
            parentVue.\$message.error('最多允许上传 {$max_files} 个文件');
            return;
        }        
        // 添加新文件
        data.forEach(file => {
            currentFiles.push({
                url: file.url,
                name: file.name || file.url.split('/').pop(),
                size: file.size || 0,
                type: file.type || file.url.split('.').pop()
            });
        });
        
        parentVue.\$set(parentVue.{$top}, '{$name}', currentFiles);
        parentVue.\$forceUpdate();
    ");

    // 注册Vue方法（增加上传前校验）
    $vue->method("vue_upload_{$name}", "
        // 数量限制检查
        if ({$max_files} > 0 && this.{$top}.{$name}?.length >= {$max_files}) {
            this.\$message.warning(`已达到最大文件数（{$max_files}个）`);
            return;
        }
        
        layer.open({
            type: 2, 
            area: ['90%', '80%'],
            content: '/admin/media/index?mime=" . $mime . "&mjs=" . urlencode($js) . "'
        });
    ");

    $vue->method("vue_remove_{$name}(index)", "
        this.{$top}.{$name}.splice(index, 1);
    ");
?>

    <!-- 文件列表表格 -->
    <div class="file-manager-container">
        <!-- 数量提示 -->
        <div v-if="<?= $max_files ?> > 0" style="margin-bottom:10px; color:#666">
            已上传 {{ <?= $top ?>.<?= $name ?>?.length || 0 }}/<?= $max_files ?> 个文件
        </div>

        <el-table
            :data="<?= $top ?>.<?= $name ?>"
            style="width: 100%"
            empty-text="<?= lang('暂无文件') ?>"
            size="small">

            <!-- 文件图标列 -->
            <el-table-column width="60">
                <template #default="{row}">
                    <div class="file-icon">
                        <img :src="getFileIcon(row.url)" v-if="!isImageFile(row.url)">
                        <el-image
                            :src="row.url"
                            :preview-src-list="[row.url]"
                            v-else
                            style="width:40px; height:40px">
                        </el-image>
                    </div>
                </template>
            </el-table-column>

            <!-- 文件名列 -->
            <el-table-column prop="name" label="<?=lang('文件名')?>">
                <template #default="{row}">
                    <span class="file-name" :title="row.name">{{ row.name }}</span>
                </template>
            </el-table-column>

            <!-- 文件大小列 -->
            <el-table-column width="120" label="<?=lang('大小')?>">
                <template #default="{row}">
                    {{ formatFileSize(row.size) }}
                </template>
            </el-table-column>

            <!-- 操作列 -->
            <el-table-column width="100">
                <template #default="{row, $index}">
                    <el-button
                        @click="window.open(row.url)"
                        size="mini"
                        type="text"
                        icon="el-icon-view">
                    </el-button>
                    <el-button
                        @click="vue_remove_<?= $name ?>($index)"
                        size="mini"
                        type="text"
                        icon="el-icon-delete"
                        style="color:#f56c6c">
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <!-- 上传按钮（动态禁用） -->
        <div style="margin-top:15px">
            <el-button
                @click="vue_upload_<?= $name ?>()"
                type="primary"
                size="small"
                icon="el-icon-upload"
                :disabled="<?= $max_files ?> > 0 && (<?= $top ?>.<?= $name ?>?.length || 0) >= <?= $max_files ?>">
                <?= lang('添加文件') ?>
                <span v-if="<?= $max_files ?> > 0">
                    (<?= lang('剩余') ?> {{ <?= $max_files ?> - (<?= $top ?>.<?= $name ?>?.length || 0) }} <?= lang('个额度') ?>)
                </span>
            </el-button>
        </div>
    </div>
<?php
}

/**
 * vue多图上传，支持拖拽排序
 */
function vue_upload_images($name = 'images', $top = 'form')
{
    global $vue;
    $js =  "
parent.layer.closeAll();
let value = parentVue." . $top . "." . $name . ";
if(!value){
    value = [];
}
for(let i = 0; i < data.length; i++){
    value.push(data[i].url);
}
parentVue.\$set(parentVue." . $top . ", '" . $name . "', value);
parentVue.\$forceUpdate();
";
    $js = aes_encode($js);
    $vue->data("vue_upload_name" . $name, "");
    $vue->method("vue_upload_all" . $name . "(name)", "
    this.vue_upload_name" . $name . " = name;
    layer.open({
        type: 2,
        title: '" . lang('上传图片') . "',
        area: ['90%', '80%'],
        content: '/admin/media/index?mjs=" . $js . "'
    });
");
    $vue->method("vue_remove_upload_all" . $name . "(index)", " 
    this." . $top . "." . $name . ".splice(index, 1); 
");
?>
    <draggable
        v-if="<?= $top ?>.<?= $name ?>"
        v-model="<?= $top ?>.<?= $name ?>"
        @start="drag=true" @end="drag=false">
        <div v-if="<?= $top ?>.<?= $name ?>" v-for="(v,index) in <?= $top ?>.<?= $name ?>"
            style="margin-bottom:5px;position: relative;width: 90px;float: left; margin-right:5px;border: 1px solid #ccc;overflow: hidden;">
            <img :src="v" style="width:90px;height: 90px;">
            <span @click="vue_remove_upload_all<?= $name ?>(index)" style="position:absolute;top:3px;right:3px;cursor: pointer;" class="bi bi-trash">
            </span>
        </div>
    </draggable>
    <div @click="vue_upload_all<?= $name ?>('<?= $name ?>')" class="link" style="
        float: left;
        width: 90px;
        height: 90px;
        line-height: 90px;
        text-align: center;
        background: #fff;
        border: 1px solid #ccc;
    ">
        <?= lang('添加图片') ?>
    </div>
    <div style="clear: both;"></div>

<?php
}
/**
 * 单图
 */
function vue_upload_image($name = 'image', $top = 'form', $show_del = false)
{
    global $vue;
    $js = "
        parent.layer.closeAll();
        parentVue.\$set(parentVue." . $top . ", '" . $name . "', data.url);
        parentVue.\$forceUpdate();
    ";
    $js = aes_encode($js);

    $vue->method("vue_upload_one" . $name . "(name)", "
        layer.open({
            type: 2,
            title: '" . lang('上传图片') . "',
            area: ['90%', '80%'],
            content: '/admin/media/index?js=" . urlencode($js) . "'
        });
    ");
    $vue->method("vue_remove_upload_one" . $name . "(index)", " 
        this." . $top . "." . $name . " = ''; 
    ");
?>

    <div v-if="<?= $top ?>.<?= $name ?>" style="margin-bottom:5px;position: relative;width: 90px;float: left; margin-right:5px;border: 1px solid #ccc;overflow: hidden;">
        <img :src="<?= $top ?>.<?= $name ?>" style="width:90px;height: 90px;">
        <span @click="vue_remove_upload_one<?= $name ?>('<?= $name ?>')" style="position:absolute;top:3px;right:3px;cursor: pointer;" class="bi bi-trash"></span>
    </div>
    <div @click="vue_upload_one<?= $name ?>('<?= $name ?>')" v-else class="link" style="
        float: left;
        width: 90px;
        height: 90px;
        line-height: 90px;
        text-align: center;
        background: #fff;
        border: 1px solid #ccc;
    ">
        <?= lang('添加图片') ?>
    </div>
    <div style="clear: both;"></div>
<?php
}
