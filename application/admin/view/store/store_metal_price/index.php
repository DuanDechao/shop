{extend name="public/container"}
{block name="content"}
<style>
    .dropdown-menu li a i{
        width: 10px;
    }
</style>
<div class="row">
    <div class="col-sm-12">
        <div class="ibox">
            <div class="ibox-title">
                <button type="button" class="btn btn-w-m btn-primary" onclick="window.location.href='{:Url('store.store_metal_price/index')}'"> 刷新</button>
                <div class="ibox-tools">

                </div>
            </div>
            <div class="ibox-content">
                <div class="table-responsive" style="overflow:visible">
                    <table class="table table-striped  table-bordered">
                        <thead>
                        <tr>

                            <th class="text-center">编号</th>
                            <th class="text-center">类型</th>
                            <th class="text-center">回购价</th>
                            <th class="text-center">销售价</th>
                            <th class="text-center">最高价</th>
                            <th class="text-center">最低价</th>
                            <th class="text-center">更新时间</th>
                        </tr>
                        </thead>
                        <tbody class="">
                        {volist name="list" id="vo"}
                        <tr>
                            <td class="text-center">
                                {$vo.id}
                            </td>
                            <td class="text-center">
                                {$vo.name}
                            </td>
                            <td class="text-center">
                                {$vo.back_price}元/g
                            </td>
                            <td class="text-center">
                                {$vo.sale_price}元/g
                            </td>
                            <td class="text-center">
                                {$vo.high_price}元/g
                            </td>
                            <td class="text-center">
                                {$vo.low_price}元/g
                            </td>
                            <td class="text-center">
                                {$vo.update_time}
                            </td>
                        </tr>
                        {/volist}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
