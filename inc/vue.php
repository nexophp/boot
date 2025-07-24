<?php

/**
 * vue多图上传，支持拖拽排序
 */
function vue_upload_all($name = 'images', $top = 'form')
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
            <img  :src="v" style="width:90px;height: 90px;">
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
function vue_upload_one($name = 'image', $top = 'form', $show_del = false)
{
    global $vue; 
    $js = "
        parent.layer.closeAll();
        parentVue.\$set(parentVue." . $top . ", '" . $name . "', data.url);
    ";
    $js = aes_encode($js);  

    $vue->method("vue_upload_one". $name."(name)", "
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
        <img :src="<?= $top ?>.<?= $name ?>"  style="width:90px;height: 90px;"> 
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
