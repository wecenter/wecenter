<?php TPL::output('admin/global/header.tpl.htm'); ?>
<?php TPL::output('admin/global/nav_menu.tpl.htm'); ?>

<div class="aw-content-wrap">
    <div class="mod">
        <div class="mod-head">
            <h3>
				<ul class="nav nav-tabs">
					<li class="<?php if ($_GET['act'] == 'index') { ?> active<?php } ?>"><a href="admin/page/"><?php _e('页面列表'); ?></a></li>
				    <li class="<?php if ($_GET['act'] == 'add') { ?> active<?php } ?>"><a href="admin/page/add/"><?php _e('添加页面'); ?></a></li>
				</ul>
            </h3>
        </div>

		<div class="mod-body tab-content">
			<div class="alert alert-success hide error_message"></div>

			<form action="admin/ajax/save_page_status/" method="post" id="page_list_form">
			<div class="table-responsive">
			<?php if ($this->page_list) { ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th><?php _e('启用'); ?></th>
							<th><?php _e('页面标题'); ?></th>
							<th width="50%"><?php _e('页面描述'); ?></th>
							<th><?php _e('操作'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->page_list AS $key => $val) { ?>
						<tr>
							<td>
								<input type="hidden" name="page_ids[<?php echo $val['id']; ?>]" value="<?php echo $val['id']; ?>" />
								<input type="checkbox" class="enabled-status" name="enabled_status[<?php echo $val['id']; ?>]" value="1"<?php if ($val['enabled']) { ?> checked="checked"<?php } ?> />
							</td>
							<td><a href="page/<?php echo $val['url_token']; ?>" target="_blank"><?php echo $val['title']; ?></a></td>
							<td width="50%"><?php echo $val['description']; ?></td>
							<td>
								<a href="admin/page/edit/id-<?php echo $val['id']; ?>" title="<?php _e('编辑'); ?>" data-toggle="tooltip" class="icon icon-edit md-tip"></a>
								<a onclick="AWS.ajax_request(G_BASE_URL + '/admin/ajax/remove_page/', 'id=<?php echo $val['id']; ?>');" title="<?php _e('删除'); ?>" data-toggle="tooltip" class="icon icon-trash md-tip"></a>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php } ?>
			</div>
			</form>
			<div class="mod-table-foot">
				<?php echo $this->pagination; ?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		$('input.enabled-status').on('ifChanged', function () {
			AWS.ajax_post($('#page_list_form'),  AWS.ajax_processer, 'error_message');
		});
	});
</script>

<?php TPL::output('admin/global/footer.tpl.htm'); ?>